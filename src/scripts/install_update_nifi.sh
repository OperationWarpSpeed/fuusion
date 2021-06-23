#!/bin/bash

PROD_PATH="/var/www/softnas"
source $PROD_PATH/scripts/nifi_version.sh
PHP="/usr/bin/php"
LOGIT="$PHP $PROD_PATH/snserver/log-it.php flexfiles.log"
CONFIGUREDREG=false


# Function to log message to stdout and to flexfiles.log
function logMsg()
{
	# $1 - Message to log

	local time=$(date)
	echo "$time - $1 --> $2"
	$LOGIT "$1" "$2"
}

# Copy selected settings from previous configuration
# Migrate from 1.1.2 to 1.5.0, 1.6.0, 1.7.1 1.9.2, 1.11.3
function migrateConfFrom_1_1_2()
{
	# $1 - Old config file
	# $2 - New config file

	declare -a ignored=("nifi.version" 
	                    "nifi.content.claim.max.appendable.size"
	                    "nifi.provenance.repository.index.threads"
	                    "nifi.variable.registry.properties"
	                    "nifi.build.*"
	                    )

	while read -r line; do
		# ignore comments and empty lines
		initial="$(echo $line | head -c 1)"
		key="$(echo $line | awk -F'=' '{ print $1 }')"
		if [ -z "$line" ] || [ -z "$key" ] || [ "$initial" = "#" ]; then
			continue
		fi
		# ignore selected settings
		found=false
		for setting in "${ignored[@]}"; do
			if echo "$key" | grep -q "$setting"; then
				found=true
				break
			fi
		done
		[ "$found" = true ] && continue;
		esc_line=$(echo $line | sed 's_/_\\/_g')
	    sed -i 's/'"$key"'=.*/'"$esc_line"'/g' "$2"
	done < "$1"
}

function configureRegistry() {
	NIFI_HOME=$(/var/www/softnas/scripts/nifi_tls_utils.sh --getNifiHome)
	if [ "$CONFIGUREDREG" = "false" ] && [ -d "$NIFI_HOME" ]; then
		# Unmonitor then stop NiFi service
		monit unmonitor NiFi
		monit unmonitor NiFiRegistry
		/var/www/softnas/scripts/nifi-service.sh stop
		cd $NIFI_HOME/
		# Install registry init script
		nifi-registry/bin/nifi-registry.sh install
		systemctl disable nifi-registry
		# Configure registry local git repo for versioned flows
		mkdir -p /opt/versioned_flows_repo
		echo 'echo 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' > ~/.git-askpass
		chmod +x ~/.git-askpass
		export GIT_ASKPASS=~/.git-askpass
		flows_repobranch=$(crudini --get /var/www/softnas/config/softnas.ini nifi-registry repo-branch 2>/dev/null | tr -d \'\")
		[ -z "$flows_repobranch" ] && flows_repobranch="prod"
		[ -d /opt/versioned_flows_repo/versioned_flows ] && GITCMD="pull" || GITCMD="clone"
		cd /opt/versioned_flows_repo
		REPO_URL="https://fuusionregister@github.com/buurst/versioned_flows.git"
		if [ "$GITCMD" = "clone" ]; then
			git clone $REPO_URL
			cd versioned_flows && git checkout $flows_repobranch
		else
			cd versioned_flows
			git remote set-url origin $REPO_URL
			git stash
			git fetch origin
			git checkout $flows_repobranch
			git pull origin $flows_repobranch
		fi
		# Copy registry nar files to NiFi's extensions folder
		mkdir -p $NIFI_HOME/extensions
		curl https://mirror.softnas.com/fuusion/software/nifi/extensions/fuusion-extension-nars.tar.gz --output - | tar -xvz -C $NIFI_HOME/extensions/
		rm -f ~/.git-askpass
		# Create link to NiFi ssl directory to reuse certs
		cd $NIFI_HOME/nifi-registry
		[ ! -e ssl ] && ln -s ../ssl ssl
		# Configure NiFi Registry env
		NIFI_REGISTRY_ENV="${NIFI_HOME}/nifi-registry/bin/nifi-registry-env.sh"
		if ! grep 'readlink' ${NIFI_REGISTRY_ENV} ; then
			echo "" >> ${NIFI_REGISTRY_ENV}
			echo 'export JAVA_HOME=$(readlink -f /usr/bin/java | sed "s:bin/java::")' >> ${NIFI_REGISTRY_ENV}
		fi
		# Cleanup database
		rm -f $NIFI_HOME/nifi-registry/database/*
		rm -f $NIFI_HOME/nifi-registry/run/*
		# Update some config settings
		/var/www/softnas/scripts/update_properties.sh "$NIFI_HOME/nifi-registry/conf/nifi-registry.properties" nifi.registry.web.https.host=0.0.0.0
		/var/www/softnas/scripts/update_properties.sh "$NIFI_HOME/nifi-registry/conf/bootstrap.conf" run.as=buurst
		# 18007 - reset nifi and nifi-registry permissions
		chown -R buurst:root $NIFI_HOME
		chmod -R 775 $NIFI_HOME
		chown -R buurst:root /opt/versioned_flows_repo
		chmod -R 775 /opt/versioned_flows_repo
		CONFIGUREDREG=true
	fi
}

# Install or update nifi
function installOrUpdateNifi()
{
	while : ; do
		rm -f /tmp/.nifi-tls-utils-*
		local CUR_HOME_EXISTS=true
		if [ -f /etc/init.d/nifi ]; then
			local HOME_DIR=$(grep NIFI_HOME= /etc/init.d/nifi | awk -F "=" '{print $2 }')
			if ! mountpoint -q $HOME_DIR && [ ! -d $HOME_DIR ]; then
				CUR_HOME_EXISTS=false
				rm -f /etc/init.d/nifi
				# Remove the flexfiles configs as actual flows are gone together with the nifi home files
				rm -f /var/www/softnas/config/flexfiles*.json
				logMsg Info "Current nifi home does not exists. Trying to recover..."
			fi
		else
			CUR_HOME_EXISTS=false
		fi
		local NIFI_INSTALL_INI="/tmp/.softnas-nifi-update.ini"
		if [ ! -f ${NIFI_INSTALL_INI} ]; then
			logMsg Info "No nifidev install/update flag available"
			local BREAK=true
			if [ "$CUR_HOME_EXISTS" = false ]; then
				cat << EOF > "/tmp/.softnas-nifi-update.ini"
[nifi]
action=install
nifi_bindir=/opt/nifidev-$nifidev_version
nifi_version=nifi-$nifi_version
EOF
				BREAK=false
			fi
			if [ "$BREAK" = true ]; then
				break
			fi
		fi

		# Unmonitor then stop NiFi service
		monit unmonitor NiFi
		monit unmonitor NiFiRegistry
		/var/www/softnas/scripts/nifi-service.sh stop

		local NIFI_BINDIR=$(grep nifi_bindir ${NIFI_INSTALL_INI} | awk -F "=" '{ print $2 }')
		local NIFI_VERSION=$(grep nifi_version ${NIFI_INSTALL_INI} | awk -F "=" '{ print $2 }')
		local NIFI_ACTION=$(grep action ${NIFI_INSTALL_INI} | awk -F "=" '{ print $2 }')
		local NIFI_HOME=${NIFI_BINDIR}/${NIFI_VERSION}
		if [ ! -f ${NIFI_BINDIR}/${NIFI_VERSION}.tar.gz ]; then
			logMsg Info "No nifidev package available for install/update"
			break
		fi
		if [ "${NIFI_ACTION}" = "install" ]; then
			NIFI_HOME="/usr/local/fuusion"
			logMsg Info "Installing nifi package..."
		elif [ "${NIFI_ACTION}" = "update" ]; then
			logMsg Info "Updating nifi package..."
		else
			logMsg Error "Invalid action!"
			break
		fi
		rm -f /opt/.nifi_installed.flag
		local CUR_HOME=$(/var/www/softnas/scripts/nifi_tls_utils.sh --getNifiHome)
		if [ ! -z "${CUR_HOME}" ] && [ -f ${CUR_HOME}/conf/.migrated ]; then
			NIFI_HOME=${CUR_HOME}
		else
			# When updating to later nifidev, move existing nifi files
			if [ ! "${NIFI_HOME}" = "${CUR_HOME}" ]; then
				rsync -tpogslr ${CUR_HOME}/* ${NIFI_HOME}/

				# Copy certs to /var/www/softnas/keys/nifi/<advertised ip>
				local ADVERTISED_IP=$(/var/www/softnas/scripts/getnificonf.sh -h)
				if [ ! -z "$ADVERTISED_IP" ]; then
					local KEYS_PATH="/var/www/softnas/keys/nifi/$ADVERTISED_IP"
					rm -f $KEYS_PATH/*
					mkdir -p $KEYS_PATH
					cp -f $NIFI_HOME/ssl/user-buurst/curl/buurst.pem $KEYS_PATH/buurst.pem
					cp -f $NIFI_HOME/ssl/user-buurst/curl/buurst.key.pem $KEYS_PATH/buurst.key.pem
					cp -f $NIFI_HOME/ssl/server.pem $KEYS_PATH/server.pem
					cp -f $NIFI_HOME/ssl/server.crt $KEYS_PATH/server.crt
				fi

				# Remove old nifi home
				logMsg Info "Removing old nifi home..."
				rm -rf  ${CUR_HOME}
			fi
		fi

		logMsg Info "Nifi's current home directory: ${NIFI_HOME}"
		mkdir -p ${NIFI_HOME}
		cd ${NIFI_HOME}
		if [ "${NIFI_ACTION}" = "update" ]; then
		   logMsg Info "Removing ${NIFI_HOME}/bin, ${NIFI_HOME}/lib, ${NIFI_HOME}/work, and ${NIFI_HOME}/docs..."
		   rm -rf bin lib docs work provenance_repository/*
		fi
		mv -f conf/nifi.properties conf/nifi.properties.old
		logMsg Info "Copying files to ${NIFI_HOME}..."
		tar --skip-old-files -xf ${NIFI_BINDIR}/${NIFI_VERSION}.tar.gz -C ${NIFI_HOME}
		chown -R softnas:softnas ${NIFI_HOME}
		${NIFI_BINDIR}/_preinstall.sh ${NIFI_BINDIR}
		su -l softnas -c "${NIFI_BINDIR}/_install.sh ${NIFI_HOME}"
		local OLD_VERSION=$(grep nifi.version conf/nifi.properties.old | awk -F "=" '{ print $2 }')
		local NEW_VERSION=$(grep nifi.version conf/nifi.properties | awk -F "=" '{ print $2 }')
		if [[ -z "${NEW_VERSION// }" ]]; then
			NEW_VERSION="$nifi_version"
		fi
		migratedConf=false
		if [ "${OLD_VERSION}" = "1.1.2" ]; then
			VERSIONS=(1.5.0 1.6.0 1.7.1 1.9.2 $nifi_version)
			for i in "${VERSIONS[@]}"; do
				if [ "${NEW_VERSION}" = "$i" ]; then
					migrateConfFrom_1_1_2 conf/nifi.properties.old conf/nifi.properties
					migratedConf=true
					break
				fi
			done
		fi
		[ "$migratedConf" = false ] && mv -f conf/nifi.properties.old conf/nifi.properties
		logMsg Info "Configure NiFi custom properties and configurations..."
		/var/www/softnas/scripts/nifi_custom_props.sh ${NIFI_HOME}
		if grep -q "CN=softnas" ${NIFI_HOME}/conf/users.xml; then
			rm -f ${NIFI_HOME}/conf/users.xml ${NIFI_HOME}/conf/authorizations.xml
		fi
		
		logMsg Info "Installing nifi..."
		cd ${NIFI_HOME}
		bin/nifi.sh install
		systemctl disable nifi
		
		# Configure NiFi Registry
		configureRegistry

		logMsg Info "Exporting JAVA_HOME environment variable"
		source /etc/profile
		source /etc/environment
		NIFI_ENV="${NIFI_HOME}/bin/nifi-env.sh"
		if ! grep 'readlink' ${NIFI_ENV} ; then
			echo "" >> ${NIFI_ENV}
			echo 'export JAVA_HOME=$(readlink -f /usr/bin/java | sed "s:bin/java::")' >> ${NIFI_ENV}
		fi
		logMsg Info "Exporting LANG=en_US.UTF-8 to support unicode filenames"
		EXPORT_LANG="export LANG=en_US.UTF-8"
		if ! grep "${EXPORT_LANG}" ${NIFI_ENV} ; then
			echo -e "\n# Use UTF-8 for nifi to support unicode filenames\n${EXPORT_LANG}" >> ${NIFI_ENV}
		fi
		rm -f ${NIFI_INSTALL_INI}
		logMsg Info "Nifi install/update done"
		touch /opt/.nifi_installed.flag
		break
	done

	# Configure NiFi Registry
	configureRegistry
}

function setupNiFi()
{
	local GETCONF_SCRIPT="/var/www/softnas/scripts/getnificonf.sh"
	local NIFI_TLS_SCRIPT="/var/www/softnas/scripts/nifi_tls_utils.sh"
	local WEBUI_PORT=`$GETCONF_SCRIPT -w`
	local ADVERTISED_IP=`$GETCONF_SCRIPT -h`	
	local DATA_PORT=`$GETCONF_SCRIPT -p`
	local ADMIN_USER=`$NIFI_TLS_SCRIPT --getAdminUser`
	local PRIVATE_IPS=`ip -f inet addr show | grep " *inet " | grep -v " lo$" | awk '{ sub (/\/.*/,""); print $2 }' | tr '\n' ' '`
	local PUBLIC_IP=`curl -k https://www.softnas.com/ip.php 2>/dev/null`
	local NIFI_HOME=$(/var/www/softnas/scripts/nifi_tls_utils.sh --getNifiHome)
	
	/var/www/softnas/scripts/nifi_custom_props.sh $NIFI_HOME
	if ! echo "$PRIVATE_IPS $PUBLIC_IP" | grep -q "\b$ADVERTISED_IP\b"; then
		ADVERTISED_IP=`echo "$PRIVATE_IPS" | awk '{ print $1 }'`
	fi
	logMsg Info "Setting up NiFi to use SSL..."
	$NIFI_TLS_SCRIPT --setupAuth --advertisedIP=$ADVERTISED_IP --webUIPort=$WEBUI_PORT --dataPort=$DATA_PORT --adminUser=$ADMIN_USER --restartNifi=true --noPreCheck
	logMsg Info "Synching httpd and nifi configurations..."
	$NIFI_TLS_SCRIPT --syncNiFiConf
}

# Install or update Nifi
installOrUpdateNifi

# Setup Nifi
setupNiFi


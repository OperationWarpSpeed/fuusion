#!/bin/bash

# PreRequirement NiFi RPM
# Script download NiFi RPM and converts to Deb
# Pushes Deb package to Fuusion Repo

# For newer versions change the below variables.

#gg MAJOR_VERSION="1.11"
#gg MINOR_VERSION="4"
#gg BUILD=$(curl https://www.softnas.com/software/softnas/nifidev/nifidev-${MAJOR_VERSION}-buildtag)
#gg NIFI_FOLDER="nifidev-${MAJOR_VERSION}"
#gg LATEST_NIFI_RPM="nifidev-${MAJOR_VERSION}-${BUILD}.x86_64"
#gg VERSION=$MAJOR_VERSION.$MINOR_VERSION-$BUILD
#gg DEB_FILE_NAME=fuusion-nifi-"$VERSION".deb
#gg 
#gg download_dir="downloads"
#gg 
#gg safe_download(){
#gg # Downloads a file from internet. If failed, reports an error and terminates
#gg # update process.
#gg #####
#gg   time wget -N "$1"
#gg   if [ "$?" != "0" ]; then
#gg                 report_status "Couldn't download \"$1\". Aborting update."
#gg                 update_failed
#gg                 exit 1
#gg   fi
#gg }
#gg 
#gg 
#gg mkdir -p "$download_dir/"
#gg pushd "$download_dir"
#gg 	safe_download https://www.softnas.com/software/softnas/package/cloudfabric/"$LATEST_NIFI_RPM".rpm
#gg         echo "Converting rpm to debian package "
#gg         alien -gck "$LATEST_NIFI_RPM".rpm
#gg 	mv $NIFI_FOLDER/debian $NIFI_FOLDER/DEBIAN
#gg 
#gg cat <<EOF > $NIFI_FOLDER/DEBIAN/control
#gg Source: nifidev
#gg Maintainer: www.buurst.com
#gg Package: fuusion-nifi
#gg Standards-Version: $MAJOR_VERSION
#gg Version: $VERSION
#gg Priority: extra
#gg Architecture: amd64
#gg Installed-Size: 2GB
#gg Pre-Depends: fuusion-jdk, git, attr, fuusion-ui, fuusion-dependency, openssh-server (=1:8.2p1-5)
#gg Description: Buurst Fuusion NiFi
#gg EOF
#gg 
#gg cat <<EOF > $NIFI_FOLDER/DEBIAN/preinst
#gg #!/bin/bash
#gg MAJOR_VERSION=$MAJOR_VERSION
#gg MINOR_VERSION=$MINOR_VERSION
#gg BUILD=$BUILD
#gg NIFI_FOLDER="nifidev-${MAJOR_VERSION}"
#gg VERSION=$MAJOR_VERSION.$MINOR_VERSION-$BUILD
#gg EOF
#gg 
#gg cat <<'EOF' >> $NIFI_FOLDER/DEBIAN/preinst
#gg INSTALLED_VER=$(dpkg -l | grep nifidev | grep -w "ii"|  awk '{print $3}')
#gg if [[ ! -z "${INSTALLED_VER}" ]]; then
#gg  echo "[nifi]" > /tmp/.softnas-nifi-update.ini
#gg  echo "action=update" >> /tmp/.softnas-nifi-update.ini
#gg  echo "nifi_bindir=/opt/$NIFI_FOLDER" >> /tmp/.softnas-nifi-update.ini
#gg  echo "nifi_version=nifi-$MAJOR_VERSION.$MINOR_VERSION" >> /tmp/.softnas-nifi-update.ini
#gg fi
#gg EOF
#gg 
#gg echo "#!/bin/bash
#gg /opt/$NIFI_FOLDER/_preinstall.sh /opt/$NIFI_FOLDER" > $NIFI_FOLDER/DEBIAN/postinst
#gg 
#gg cat <<'EOF' >> $NIFI_FOLDER/DEBIAN/postinst
#gg if ! grep -q "^export[[:space:]]\+PATH=.*" $HOME/.bashrc; then
#gg     echo "export PATH=" >> $HOME/.bashrc
#gg fi
#gg sed -i 's/PATH=.*/PATH=$PATH:$HOME\/bin:\/opt\/apache-maven-'"3.3.9"'\/bin/g' $HOME/.bashrc
#gg if ! grep -q "^export[[:space:]]\+JAVA_HOME=" $HOME/.bashrc; then
#gg     echo "export JAVA_HOME=" >> $HOME/.bashrc
#gg fi
#gg sed -i 's/JAVA_HOME=.*/JAVA_HOME=$(readlink -f \/usr\/bin\/java | sed "s:bin\/java::")/g' $HOME/.bashrc
#gg 
#gg #Run NiFi Installer
#gg /var/www/buurst/scripts/install_update_nifi.sh
#gg /var/www/buurst/scripts/nifi-service.sh restart
#gg /var/www/buurst/scripts/nifi_tls_utils.sh --waitNifi
#gg NIFI_HOME=$(/var/www/buurst/scripts/nifi_tls_utils.sh --getNifiHome)
#gg NIFI_HTTPS_HOST=$(cat $NIFI_HOME/conf/nifi.properties | grep nifi.web.https.host | awk -F"=" '{ print $2 }')
#gg REGISTRYPORT=$(cat $NIFI_HOME/nifi-registry/conf/nifi-registry.properties | grep nifi.registry.web.https.port | awk -F"=" '{ print $2 }')
#gg NIFICMD="/var/www/buurst/snserver/nifi/nificmd.php"
#gg if [ ! -f "$NIFICMD" ]; then
#gg   NIFICMD="/var/www/buurst/snserver/LiftAndShift/nificmd.php"
#gg fi
#gg php $NIFICMD --add_registry_client --registry_name "buurst-registry" \
#gg   --registry_url https://$NIFI_HTTPS_HOST:$REGISTRYPORT --registry_desc "Buurst's default registry"
#gg 
#gg EOF
#gg 
#gg  pushd $NIFI_FOLDER/DEBIAN 
#gg   chmod 755 preinst prerm postinst
#gg   rm -f changelog compat copyright
#gg  popd
#gg 
#gg  dpkg-deb --build $NIFI_FOLDER
#gg  mv $NIFI_FOLDER.deb $DEB_FILE_NAME
#gg popd
#gg 

# NIFI Docker installation

function exit_status  {
	if [ $? -ne 0 ]
	then
		echo $1
		exit 1
	fi
}

DOCKER_VER=$(dpkg -l | grep docker-ce|grep -v docker-ce-|awk '{print $3}')

if [ -z "$DOCKER_VER" ]
then
	apt install apt-transport-https ca-certificates curl software-properties-common -y
	exit_status "not able to install docker prerequisite packages"
	curl -fsSL https://download.docker.com/linux/ubuntu/gpg |  apt-key add -
	add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu focal stable" -y
	exit_status "not able to add docker repo"
 	apt update -y
 	exit_status "not able to update apt"
 	apt install docker-ce -y
 	exit_status "not able to install docker-ce"
fi

DOCKER_PID=$(ps -ef |grep docker|grep -v grep|awk '{print $2}')

if [ -z "$DOCKER_PID" ]
then
	if [ ! -z "$DOCKER_VER " ]
	then
		rm -f /var/run/docker.sock
		service docker start
		exit_status "not able to start docker"
		docker run -d apache/nifi
		exit_status "not able to start nifi container"
	fi
 else
 	docker run -d apache/nifi
 	exit_status "not able to start nifi container"
fi


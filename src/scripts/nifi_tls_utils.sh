#!/bin/bash
# Copyright (c) 2020-2021 Buurst, Inc.

PID=$$
OK_RETVAL=0
OK_MSG="Operation successful"
ERR_RETVAL=1
ERR_MSG="Operation failed"
RETVAL=$OK_RETVAL
RETMSG="$OK_MSG"
PROD_PATH="/var/www/softnas"
UPDATE_LOG=""
ADVERTISED_IP=""
WEBUI_PORT="9443"
DATA_PORT="8081"
ADMIN_USER="admin"
LOG_LEVEL="Debug"
NIFI_UPTIME_SECS=300 # Nifi 1.5.0 takes about 5min to completely start
NIFI_UPTIME_STR="$((NIFI_UPTIME_SECS/60)) minutes"

source $PROD_PATH/scripts/nifi_version.sh
PHP="/usr/bin/php"
LOGIT="$PHP $PROD_PATH/snserver/log-it.php flexfiles.log"

trap "${LOGIT} Info \"Error detected in nifi_tls_utils.sh!\"; echo \"ERROR!\"; date; exit 1" ERR

# If no parameters, display help
if [ -z $1 ]; then
	$0 --help
	exit 0
fi

source /etc/environment

############################################################
# Code for parsing command line arguments                  #
############################################################
TEMP=`getopt -o h,s,c,x,X,S,C,l,a,P,i:,w:,d:,u:,n:,H:,r:,p:,e,E:,R:,L,U:,M,N:,g,m,D,k,K,W,o --long help,\
setupAuth,createUser,exchangeCerts,exchangeKeys,setupAuthRemote,syncNiFiConf,sshPassLogin,getAdminUser,areNodesPaired,advertisedIP:,\
webUIPort:,dataPort:,adminUser:,userName:,homeDir:,remoteNode:,passWord:,echoOn,enable:,restartNifi:,localOnly,updateLog:,migrateNifiHome,\
newHomeDir:,getNifiHome,monitorNifi,checkPending,noPreCheck,checkNifi,waitNifi,getSnapRole -n 'nifi_tls_utils.sh' -- "$@"`
eval set -- "$TEMP"

# extract options and their arguments into variables.
while true ; do
    case "$1" in
        -h|--help) echo "
NAME
	./nifi_tls_utils.sh - Setup nifi to use SSL

SYNOPSIS
	./nifi_tls_utils.sh	[--setupAuth | --createUser | --exchangeCerts | --setupAuthRemote | --syncNiFiConf | --sshPassLogin |
		--migrateNifiHome | --getNifiHome | --monitorNifi | --checkPending | --checkNifi ]
		[--advertisedIP=ip] [--webUIPort=port_number] [--dataPort=data_port] [--adminUser=user_name] 
		[--remoteNode=ip/dns] [--passWord=password] [--homeDir=home directory] [--echoOn] [enable=true/false]
		[--restartNifi=true/false] [--updateLog=/tmp/exchange-certs.log] [ --newHomeDir=/s3pool/s3vol/nifi ]

	OPTIONS:
		--setupAuth       - Setup keystore, truststore, and default users for nifi access over SSL and authentication
		--setupAuthRemote - Perform --setupAuth at remote node
		--createUser      - Create user keys/certs (specify --userName option)
		--exchangeCerts   - Exchange cert with remote NiFi server and add each other's cert into each other's truststore
		--syncNiFiConf    - Sync nifi.conf with values from nifi.properties
		--sshPassLogin    - Enables password login for SSH
		--areNodesPaired  - Checks if this node is paired with specified remote node
		--exchangeKeys    - Exchange ssh keys with remote
		--migrateNifiHome - Migrate nifi home to a new cloud directory
		--getNifiHome     - Gets current nifi home directory
		--monitorNifi     - Enable/disable nifi monitoring
		--checkPending    - Check if there is pending nifi configuration
		--checkNifi       - Check if remote NiFi that is paired with this local is accessible or not
		--waitNifi        - Starts local NiFi if stopped and wait until it is ready
		--getSnapRole     - Gets the snapreplicate role of a paired remote node

	REQUIRED OPTIONS:
		--advertisedIP    - Specifies the IP to be used to access this NiFi server (defaults to one of private ips)
		--webUIPort       - Specifies the port to access the NiFi flow UI (defaults to 9443)
		--dataPort        - Specifies the port for site-to-site communication (defaults to 8081)
		--adminUser       - Specifies the user name with default admin role (defaults to admin)
		--userName        - Specifies the user name if --createUser, --exchangeCerts or --setupAuthRemote option is specified
		--passWord        - Specifies the user's password for --exchangeCerts or --setupAuthRemote option
		--remoteNode      - Specifies the IP/DNS of the remote node for --exchangeCerts or --setupAuthRemote option
		--enable          - Enables/disables specified option such as for --sshPassLogin
		--restartNifi     - Restart nifi service and wait until up or not
		--newHomeDir      - New cloud directory where to migrate nifi home
		
	OPTIONAL OPTIONS:
		--homeDir         - Specifies the NiFi home directory (defaults to /opt/nifidev-1.7/nifi-1.7.1)
		--echoOn          - Does not redirect stdout and stderr logs into /dev/null (for debugging purposes)
		--localOnly       - In case of --areNodesPaired, checks both ways if specified, otherwise, one-way only from local
		--updateLog       - File to contain progress logs of the specified operation
		--noPreCheck      - No precheck before setting up nifi

EXAMPLES
	1. Setup NiFi to use SSL
		./nifi_tls_utils.sh --setupAuth --advertisedIP=<ip> --webUIPort=<port number> --dataPort=<port number> 
			--adminUser=<admin user> --homeDir=<NiFi home directory>
	
	2. Setup NiFi to use SSL with using defaults for some/all options
		./nifi_tls_utils.sh --setupAuth
		./nifi_tls_utils.sh --setupAuth --advertisedIP=172.16.0.1
		./nifi_tls_utils.sh --setupAuth --advertisedIP=172.16.0.1 --webUIPort=9444
		./nifi_tls_utils.sh --setupAuth --advertisedIP=172.16.0.1 --webUIPort=9444 --dataPort=8082
		./nifi_tls_utils.sh --setupAuth --advertisedIP=172.16.0.1 --webUIPort=9444 --dataPort=8082 --adminUser=user1
		./nifi_tls_utils.sh --setupAuth --webUIPort=9444 --dataPort=8082 --adminUser=user1 --homeDir=/opt/nifi

	3. Create user keys
		./nifi_tls_utils.sh --createUser --userName=user1

	4. Exchange certs with a remote NiFi server
		./nifi_tls_utils.sh --exchangeCerts --remoteNode=10.0.0.224 --userName=buurst --passWord=Pass4W0rd --updateLog=/tmp/nifi-tls.log
		./nifi_tls_utils.sh --exchangeCerts --remoteNode=10.0.0.224 --userName=buurst --passWord=Pass4W0rd --homeDir=/opt/nifi

	5. Setup remote NiFi server to use SSL
		./nifi_tls_utils.sh --setupAuthRemote --remoteNode=10.0.0.224 --userName=buurst --passWord=Pass4W0rd
				--advertisedIP=10.0.0.224 --webUIPort=9443 --dataPort=8081 --adminUser=admin

	6. Enable/disable password login for SSH
		./nifi_tls_utils.sh --sshPassLogin enable=true --remoteNode=10.0.0.24 --userName=buurst --passWord=Pass4W0rd
		./nifi_tls_utils.sh --sshPassLogin enable=false --remoteNode=10.0.0.24 --userName=buurst --passWord=Pass4W0rd

	7. Restart nifi service after a configuration change or not
		./nifi_tls_utils.sh --setupAuth --advertisedIP=172.16.0.1 --webUIPort=9444 --dataPort=8082 --restartNifi=true ...
		./nifi_tls_utils.sh --setupAuth --advertisedIP=172.16.0.1 --webUIPort=9444 --dataPort=8082 --restartNifi=false ...

	8. Sync /etc/httpd/conf.d/nifi.conf with values from $NIFI_HOME/conf/nifi.properties
		./nifi_tls_utils.sh --syncNiFiConf

	9. Get current local nifi admin user
		./nifi_tls_utils.sh --getAdminUser

	10. Check if this node is already paired with the specified remote
		./nifi_tls_utils.sh --areNodesPaired --remoteNode=172.16.0.1 --userName=buurst --passWord=Pass4W0rd --webUIPort=9444

	11. Exchange ssh keys with remote node
		./nifi_tls_utils.sh --exchangeKeys --remoteNode=10.0.0.224 --userName=buurst --passWord=Pass4W0rd

	12. Migrate nifi home directory to a cloud disk directory
		./nifi_tls_utils.sh --migrateNifiHome --homeDir=/orig/location --newHomeDir=/new/location

	13. Get the current nifi home directory
		./nifi_tls_utils.sh --getNifiHome

	14. Enable nifi monitoring
		./nifi_tls_utils.sh --monitorNifi --enable=true

	15. Check pending nifi configuration process
		./nifi_tls_utils.sh --checkPending

	16. Check if remote nifi is accessible or not
		./nifi_tls_utils.sh --checkNifi --remoteNode=172.16.0.1 --webUIPort=9443 --dataPort=8081

	17. Check if remote nifi is accessible or not and attempt to restart it if not accessible
		./nifi_tls_utils.sh --checkNifi --remoteNode=IP --webUIPort=Port --dataPort=Port --userName=user --passWord=pass --restartNifi=true

	18. Start if local nifi is stopped and wait until it is ready
		./nifi_tls_utils.sh --waitNifi

	19. Get the snapreplicate role of a specified paired remote
                ./nifi_tls_utils.sh --getSnapRole --remoteNode=172.16.0.1 --userName=user --passWord=pass

	Note: Generated files will be stored in $NIFI_HOME/ssl

======================================== END =========================================
"
			HELP="true"
			exit 0
			shift ;;
		-i|--advertisedIP)
			case "$2" in
				"") shift 2 ;;
				*) ADVERTISED_IP=$2 ;
				shift 2 ;;
			esac ;;
		-w|--webUIPort)
			case "$2" in
				"") shift 2 ;;
				*) WEBUI_PORT=$2 ;
				shift 2 ;;
			esac ;;
		-d|--dataPort)
			case "$2" in
				"") shift 2 ;;
				*) DATA_PORT=$2 ;
				shift 2 ;;
			esac ;; 
		-u|--adminUser)
			case "$2" in
				"") shift 2 ;;
				*) ADMIN_USER=$2 ;
				shift 2 ;;
			esac ;;
		-n|--userName)
			case "$2" in
				"") shift 2 ;;
				*) USER_NAME=$2 ;
				shift 2 ;;
			esac ;;
		-H|--homeDir)
			case "$2" in
				"") shift 2 ;;
				*) HOME_DIR=$2 ;
				shift 2 ;;
			esac ;;
		-r|--remoteNode)
			case "$2" in
				"") shift 2 ;;
				*) REMOTE_NODE=$2 ;
				shift 2 ;;
			esac ;;
		-p|--passWord)
			case "$2" in
				"") shift 2 ;;
				*) PASSWD=$2 ;
				shift 2 ;;
			esac ;;
		-E|--enable)
			case "$2" in
				"") shift 2 ;;
				*) ENABLE=$2 ;
				shift 2 ;;
			esac ;;
		-R|--restartNifi)
			case "$2" in
				"") shift 2 ;;
				*) RESTART_NIFI=$2 ;
				shift 2 ;;
			esac ;;
		-U|--updateLog)
			case "$2" in
				"") shift 2 ;;
				*) UPDATE_LOG=$2 ;
				shift 2 ;;
			esac ;;
		-N|--newHomeDir)
			case "$2" in
				"") shift 2 ;;
				*) NEW_HOME_DIR=$2 ;
				shift 2 ;;
			esac ;;
		-s|--setupAuth) SETUP_AUTH=true ; shift ;;
		-S|--setupAuthRemote) SETUP_AUTH_REM=true ; shift ;;
		-c|--createUser) CREATE_USER=true ; shift ;;
		-x|--exchangeCerts) EXCHANGE_CERTS=true ; shift ;;
		-X|--exchangeKeys) EXCHANGE_KEYS=true ; shift ;;
		-C|--syncNiFiConf) SYNC_NIFI_CONF=true ; shift ;;
		-l|--sshPassLogin) SSH_PASS_LOGIN=true ; shift ;;
		-a|--getAdminUser) GET_ADMIN_USER=true ; shift ;;
		-P|--areNodesPaired) ARE_NODES_PAIRED=true ; shift ;;
		-e|--echoOn) ECHO_ON=true ; shift ;;
		-L|--localOnly) LOCAL_ONLY=true ; shift ;;
		-M|--migrateNifiHome) MIGRATE_NIFI_HOME=true ; shift ;;
		-g|--getNifiHome) GET_NIFI_HOME=true ; shift ;;
		-m|--monitorNifi) MONITOR_NIFI=true ; shift ;;
		-D|--checkPending) CHECK_PENDING=true ; shift ;;
		-k|--noPreCheck) NO_PRECHECK=true ; shift ;;
		-K|--checkNifi) CHECK_NIFI=true ; shift ;;
		-W|--waitNifi) WAIT_NIFI=true ; shift ;;
		-o|--getSnapRole) GET_SNAP_ROLE=true ; shift ;;
		--) shift ; break ;;
		*) echo "Incorrect Syntax!" ; 
		exit 1 ;;
	esac
done

function setRetVal() 
{
	# $1 = RETVAL value
	# $2 = RETMSG value

	RETVAL=$OK_RETVAL
	RETMSG="$OK_MSG"
	if [ ! -z "$1" ]; then
		RETVAL=$1
		if [ $RETVAL -ne $OK_RETVAL ]; then
			RETMSG="$ERR_MSG"
		fi
	fi
	if [ ! -z "$2" ]; then
		RETMSG="$2"
	fi
}

function logger()
{
	# $1 = Log type
	# $2 = Log message

	RETVAL=$OK_RETVAL
	RETMSG="$2"
	if [ "$1" == "Error" ]; then
		RETVAL=$ERR_RETVAL
	fi
	if [ "$(id -u)" == "0" ]; then
		$LOGIT $1 "$2"
	fi
}

function logError()
{
	# $1 = Error message

	local MSG="$ERR_MSG"
	if [ ! -z "$1" ]; then
		MSG="$1"
	fi
	logger Error "$MSG"
}

function logSuccess()
{
	# $1 = Success message

	local MSG="$OK_MSG"
	if [ ! -z "$1" ]; then
		MSG="$1"
	fi
	logger $LOG_LEVEL "$MSG"
}

function logUpdate()
{
	# $1 = Update message
	if [ ! -z "$UPDATE_LOG" ] && [ ! -z "$1" ]; then
		echo "$1" >> "$UPDATE_LOG"
	fi
}

function getAllIPs() 
{
	# Get private IPs
	PRIVATE_IPS=`ip -f inet addr show | grep " *inet " | grep -v " lo$" | awk '{ sub (/\/.*/,""); print $2 }'`
	echo -e "$PRIVATE_IPS" > $1

	# Get public IP in case of Amazon EC2
	PUBLIC_IP=`curl --connect-timeout 2 http://169.254.169.254/latest/meta-data/public-ipv4 2>/dev/null`
	echo $PUBLIC_IP | grep -oE "\b([0-9]{1,3}\.){3}[0-9]{1,3}\b" &>/dev/null
	if [ $? -ne 0 ]; then
		# Use generic way of getting public ip
		PUBLIC_IP=`curl -k https://www.softnas.com/ip.php`
	fi
	if [ ! -z "$PUBLIC_IP" ]; then
		echo $PUBLIC_IP >> $1
	fi
}

function getSANExts() 
{
	FOUND_ADVIP="0"
	while read IPSinLine
	do
		for IPinLine in $IPSinLine
		do
			if [ -z "$ADVERTISED_IP" ]; then
				ADVERTISED_IP="$IPinLine"
			fi
			if [ -z "$SAN_EXTS" ]; then
				SAN_EXTS="-ext SAN=IP:$IPinLine"
			else
				SAN_EXTS="$SAN_EXTS,IP:$IPinLine"
			fi
			if [ "$IPinLine" == "$ADVERTISED_IP" ]; then
				FOUND_ADVIP="1"
			fi
		done
	done < "${1:-/dev/stdin}"

	if [ "$FOUND_ADVIP" == "0" ]; then
		if [ -z "$SAN_EXTS" ]; then
			SAN_EXTS="-ext SAN=IP:$ADVERTISED_IP"
		else
			SAN_EXTS="$SAN_EXTS,IP:$ADVERTISED_IP"
		fi
	fi
}

function createUser() 
{
	USER_DIR="$SSL_DIR/user-$1"
	mkdir -p $USER_DIR
	
	${LOGIT} Info "Creating user key pair for user $1 using password $2"
	EXTS="-ext BC=ca:false -ext KU=nonRepudiation,digitalSignature,keyEncipherment"
	keytool -genkeypair -keyalg RSA -keysize 2048 -validity 3650 -keystore $USER_DIR/$1.jks -alias $1 -keypass $2 -storepass $2 $EXTS \
			-dname CN=$1\,O=Buurst\,L=Houston\,ST=TX\,C=US
	keytool -exportcert -keystore $USER_DIR/$1.jks -storepass $2 -alias $1 -file $USER_DIR/$1.crt -rfc
	
	${LOGIT} Info "Converting JKS to PKCS12 for browser import"
	keytool -importkeystore -srckeystore $USER_DIR/$1.jks -destkeystore $USER_DIR/$1.p12 -srcstoretype JKS -deststoretype PKCS12 \
			-srcstorepass $2 -deststorepass $2 -srcalias $1 -destalias user-$1 -srckeypass $2 -destkeypass $2 -noprompt

	${LOGIT} Info "Saving password for the keypair into a file..."
	echo $2 > $USER_DIR/$1.passwd

	${LOGIT} Info "Extracting private key and public cert for curl..."
	mkdir -p $USER_DIR/curl
	openssl pkcs12 -in $USER_DIR/$1.p12 -out $USER_DIR/curl/$1.key.pem -nocerts -nodes -passin file:$USER_DIR/$1.passwd
	openssl pkcs12 -in $USER_DIR/$1.p12 -out $USER_DIR/curl/$1.crt.pem -clcerts -nokeys -passin file:$USER_DIR/$1.passwd
	openssl rsa -in $USER_DIR/curl/$1.key.pem -out $USER_DIR/curl/key.pem
	cp $USER_DIR/curl/$1.crt.pem $USER_DIR/curl/$1.pem 
	cat $USER_DIR/curl/key.pem >> $USER_DIR/curl/$1.pem
	rm -f $USER_DIR/curl/key.pem
	pushd $USER_DIR
	rm -f ./$1.pem
	cp -f ./curl/$1.pem ./$1.pem
	cp -f ./curl/$1.key.pem ./$1.key.pem
	${LOGIT} Info "Zipping P12 file and password for user download..."
	tar -czf $1.tar.gz $1.p12 $1.passwd
	popd
}

function syncNiFiConf()
{
	${LOGIT} Info "Synching nginx nifi conf..."
	local WEBUI_PORT=`$GTCONF_SCRIPT -w`
	local ADVERTISED_IP=`$GTCONF_SCRIPT -h`

	if [ -z "$WEBUI_PORT" ] || [ -z "$ADVERTISED_IP" ]; then
		${LOGIT} Error "Empty web ui port or advertised ip ($WEBUI_PORT, $ADVERTISED_IP)"
	else
		TEMPLATE_NIFI_CONF="/var/www/softnas/templates/nifi.conf.nginx"
		TEMP_NIFI_CONF="/tmp/.nifi.conf.nginx"

		cp -f $TEMPLATE_NIFI_CONF $TEMP_NIFI_CONF
		sed -i 's/[#]\?proxy_ssl/proxy_ssl/g' $TEMP_NIFI_CONF
		sed -i 's/FlexFiles_Port/'"$WEBUI_PORT"'/g' $TEMP_NIFI_CONF
		mv -f $TEMP_NIFI_CONF /etc/nginx/conf.d/nifi.conf.nginx

		# Copy this node's certs folder for /etc/httpd/conf.d/nifi.conf
		local KEYS_PATH="/var/www/softnas/keys/nifi"
		rm -rf $KEYS_PATH/localhost
		ln -s $KEYS_PATH/$ADVERTISED_IP $KEYS_PATH/localhost
		nginx -s reload
	fi
}

function canConnect() 
{
	# $1 = Host DNS/IP
	# $2 = Port
	# $3 = true/false (check localhost connectivity if true)
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	local CONNECTED=false
	${LOGIT} Info "Trying to connect to $1:$2..."
	curl -v -I --connect-timeout 5 --max-time 2 $1:$2 > /tmp/.canConnect.txt 2>&1
	cat /tmp/.canConnect.txt | grep "Connected to $1" >/dev/null 2>&1
	if [ $? -eq 0 ]; then
		${LOGIT} Info "Connected to $1:$2..."
		CONNECTED=true
	elif [ "$3" = true ]; then
		${LOGIT} Info "Retrying to connect but to 127.0.0.1:$2..."
		curl -v -I --connect-timeout 5 --max-time 2 127.0.0.1:$2 > /tmp/.canConnect.txt 2>&1
		cat /tmp/.canConnect.txt | grep "Connected to 127.0.0.1" >/dev/null 2>&1
		if [ $? -eq 0 ]; then
			${LOGIT} Info "Connected to 127.0.0.1:$2..."
			CONNECTED=true
		fi
	fi
	rm -f /tmp/.canConnect.txt
	if [ "$CONNECTED" = true ]; then
		logSuccess "Successfully connected to $1:$2"
	else
		logError "Failed to connect to $1:$2"
	fi
}

function canAccess() 
{
	# $1 = Host DNS/IP
	# $2 = Port
	# $3 = true/false (check localhost connectivity if true)
	# $4 = Location of cert for curl
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	local ACCESSIBLE=false
	${LOGIT} Info "Checking user access to https://$1:$2/nifi using cert $4..."
	local LIMIT_OPTS="--connect-timeout 5 --max-time 5"
	curl --cert $4 -k $LIMIT_OPTS https://$1:$2/nifi-api/access/ 2>&1 | grep "You are already logged in"
	if [ $? -eq 0 ]; then
		ACCESSIBLE=true
	elif [ "$3" = true ]; then
		${LOGIT} Info "Retry checking user access but to https://127.0.0.1:$2/nifi..."
		curl --cert $4 -k $LIMIT_OPTS https://127.0.0.1:$2/nifi-api/access/ 2>&1 | grep "You are already logged in"
		if [ $? -eq 0 ]; then
			ACCESSIBLE=true
		fi
	fi
	if [ "$ACCESSIBLE" = true ]; then
		logSuccess "https://$1:$2/nifi is accessible to user"
	else
		logError "https://$1:$2/nifi is not accessible to user"
	fi
}

function isReachable()
{
	# $1 = Host DNS/IP
	# $2 = Port
	# $3 = true/false (check localhost connectivity if true)
	# $4 = Timeout in seconds
	# $5 = Retry interval in seconds
	# $6 = true/false (Is $2 equal to web ui port?)
	# $7 = If $6 is true, this is path to user cert
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	${LOGIT} Info "Trying to reach $1:$2..."
	local TIMEOUT=$4  # Timeout in seconds
	local INTERVAL=$5 # Check interval in seconds
	local CHKCOUNT=$(($TIMEOUT/$INTERVAL))

	logUpdate "Trying to reach $1:$2..."
	for i in `seq 1 $CHKCOUNT`;
	do
		canConnect $1 $2 $3
		if [ $RETVAL -eq $OK_RETVAL ]; then
			if [ "$6" = true ]; then
				canAccess $1 $2 $3 $7
			fi
			if [ $RETVAL -eq $OK_RETVAL ]; then
				logUpdate "Attempt $i of $CHKCOUNT: Connected to $1:$2."
				break;
			fi
		fi
		logUpdate "Attempt $i of $CHKCOUNT: Unable to reach $1:$2. Retrying..."
		${LOGIT} Info "Retrying to reach $1:$2..."
		sleep $INTERVAL
	done
	if [ $RETVAL -eq $OK_RETVAL ]; then
		logSuccess "$1:$2 is reachable"
	else
		logError "$1:$2 is not reachable"
		logUpdate "Attempt $CHKCOUNT of $CHKCOUNT: Unable to reach $1:$2. Exiting..."
	fi
}

function getProperties() 
{
	CUR_IP=`$GTCONF_SCRIPT -h`
	CUR_WEBPORT=`$GTCONF_SCRIPT -w`
	CUR_DATAPORT=`$GTCONF_SCRIPT -p`
}

function isNodeSetup()
{
	# $1 = Node DNS/IP
	# $2 = Web UI Port
	# $3 = Data Port
	# $4 = true/false (check localhost connectivity if true)
	# $5 = admin user
	# $6 = Timeout in checking if nifi service is up (seconds)
	# $7 = Retry interval (seconds)
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	${LOGIT} Info "Verifying if nifi server is already configured for secure SSL access..."
	
	# Verify first if parameters passed were different from current
	${LOGIT} Info "Checking if passed params are same with current..."
	getProperties
	local PREV_ADMIN=""
	if [ -f $SSL_DIR/.admin_user.txt ]; then
		PREV_ADMIN=`cat $SSL_DIR/.admin_user.txt`
	fi
	# Assume error by default
	setRetVal $ERR_RETVAL
	if [ "$CUR_IP" == "$1" ] && [ "$CUR_WEBPORT" == "$2" ] && [ "$CUR_DATAPORT" == "$3" ] && [ "$PREV_ADMIN" == "$5" ]; then
		logSuccess "Passed params are same with current settings"
	fi

	# Trying to connect via web ui port
	if [ $RETVAL -eq $OK_RETVAL ]; then
		local CERT="$SSL_DIR/user-buurst/curl/buurst.pem"
		isReachable $1 $2 $4 $6 $7 true $CERT
		CERT="$SSL_DIR/server.pem"
		if [ $RETVAL -eq $OK_RETVAL ] && [ -f $CERT ]; then
			isReachable $1 $2 $4 $6 $7 true $CERT
		fi
	fi

	# Trying to connect via data port
	if [ $RETVAL -eq $OK_RETVAL ]; then
		isReachable $1 $3 $4 $6 $7 false
	fi

	# Make sure all current IP is in SubjectAlternativeName of this server's cert
	local CERTPASS=$(cat $SSL_DIR/.passwd)
	local CERTINFO=$(keytool -list --storepass $CERTPASS  -keystore $SSL_DIR/keystore.jks -alias self -v)
	local TMP_IPS="/tmp/.tmp_server_ips.txt"
	getAllIPs $TMP_IPS
	while IFS= read -r IPAddress; do
		if ! echo "$CERTINFO" | grep -q "IPAddress: $IPAddress"; then
			logError "The $IPAddress instance IP is not in SubjectAlternativeName of this server's cert"
			rm -f $SSL_DIR/keystore.jks
			break
		fi
	done < $TMP_IPS

	# Logging result
	if [ $RETVAL -eq $OK_RETVAL ]; then
		logSuccess "Nifi server is already configured for secure SSL access"
	else
		logError "Nifi server is not configured for secure SSL access"
	fi
}

function exchangeSshKeys() 
{
	# $1 = "true" means return after adding public key to remote
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	while : ; do
		# Assume error by default
		setRetVal $ERR_RETVAL

		local REMCMD=""
		local PUBKEY=""
		local LOCAL_IP=`$GTCONF_SCRIPT -h`
		local PASS_FILE="/tmp/.nifi_ssh_passwd"
		local SSH_UTILS_SCRIPT="/var/www/softnas/scripts/nifi_ssh_utils.sh"

		${LOGIT} Info "Exchanging local's ($LOCAL_IP) ssh keys with remote ($REMOTE_NODE)..."

		enableSshPassLogin true
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
		echo $PASSWD > $PASS_FILE

		${LOGIT} Info "Creating keys if not yet created..."
		$SSH_UTILS_SCRIPT --createKeys --userName=root --echoOn
		if [ $? -ne 0 ]; then
			logError "Failed to create ssh key pair for root at local ($LOCAL_IP)"
			break
		fi

		# Add remote's hashed key to local's known_hosts
		PUBKEY=`cat ~/.ssh/id_rsa.pub`
		${LOGIT} Info "Adding remote's hashed key into known_hosts..."
		$SSH_UTILS_SCRIPT --addKnownHost --userName=root --remoteNode=$REMOTE_NODE --echoOn
		if [ $? -ne 0 ]; then
			logError "The local (root@$LOCAL_IP) failed to accept ssh host key of remote ($REMOTE_NODE)"
			break
		fi

		# Add local's public key to remote's authorized_keys
		${LOGIT} Info "Adding public key to authorized_keys of $USER_NAME and root at $REMOTE_NODE..."
		ssh -o PubkeyAuthentication=yes  -o PasswordAuthentication=no $USER_NAME@$REMOTE_NODE "uname -a"
		if [ $? -ne 0 ]; then
			sshpass -f $PASS_FILE ssh-copy-id $USER_NAME@$REMOTE_NODE
			if [ $? -ne 0 ]; then
				logError "Failed to add root's public key into authorized_keys of $USER_NAME@$REMOTE_NODE"
				break
			fi
		fi
		REMCMD="$SSH_UTILS_SCRIPT --createKey --userName=$USER_NAME --echoOn"
		ssh $USER_NAME@$REMOTE_NODE "$REMCMD"
		if [ $? -ne 0 ]; then
			logError "Failed to create ssh key pair for $USER_NAME@$REMOTE_NODE"
			break
		fi
		if [ "$USER_NAME" != "root" ]; then
			ssh -o PubkeyAuthentication=yes  -o PasswordAuthentication=no root@$REMOTE_NODE "uname -a"
			if [ $? -ne 0 ]; then
				REMCMD="$SSH_UTILS_SCRIPT --addAuthorizedKey --userName=root --publicKey='$PUBKEY' --echoOn"
				ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS env TERM=dumb $REMCMD"
				if [ $? -ne 0 ]; then
					logError "Failed to add root's public key into authorized_keys of root@$REMOTE_NODE"
					break
				fi
			else
				REMCMD="$SSH_UTILS_SCRIPT --createKey --userName=root --echoOn"
				ssh root@$REMOTE_NODE "$REMCMD"
				if [ $? -ne 0 ]; then
					logError "Failed to create ssh key pair for root@$REMOTE_NODE"
					break
				fi
			fi
		fi

		# Testing from local to remote
		${LOGIT} Info "Testing ssh connection from local to remote..."
		SSH_OPT="-o PubkeyAuthentication=yes  -o PasswordAuthentication=no"
		ssh $SSH_OPT $USER_NAME@$REMOTE_NODE "uname -a"
		if [ $? -ne 0 ]; then
			logError "This local (root@$LOCAL_IP) can't ssh to remote ($USER_NAME@$REMOTE_NODE)"
			break
		fi
		ssh $SSH_OPT root@$REMOTE_NODE "uname -a"
		if [ $? -ne 0 ]; then
			logError "This local (root@$LOCAL_IP) can't ssh to remote (root@$REMOTE_NODE)"
			break
		fi

		# Discontinue with the 2-way ssh exchange keys?
		if [ ! "$1" = true ]; then
			# Add remote's public key into local's authorized_keys
			${LOGIT} Info "Adding public key of both $USER_NAME and/or root of remote into authorized_keys..."
			PUBKEY=`ssh $USER_NAME@$REMOTE_NODE "cat ~/.ssh/id_rsa.pub"`
			$SSH_UTILS_SCRIPT --addAuthorizedKey --userName=root --publicKey="$PUBKEY" --echoOn
			if [ $? -ne 0 ]; then
				logError "Failed to add public key of $USER_NAME@$REMOTE_NODE into authorized_keys"
				break
			fi
			if [ "$USER_NAME" != "root" ]; then
				PUBKEY=`ssh root@$REMOTE_NODE "cat ~/.ssh/id_rsa.pub"`
				$SSH_UTILS_SCRIPT --addAuthorizedKey --userName=root --publicKey="$PUBKEY" --echoOn
				if [ $? -ne 0 ]; then
					logError "Failed to add public key of root@$REMOTE_NODE into authorized_keys"
					break
				fi
			fi

			# Add local's key to remote' known_hosts
			${LOGIT} Info "Adding local's key to remote's known_hosts..."
			REMCMD="$SSH_UTILS_SCRIPT --addKnownHost --userName=$USER_NAME --remoteNode=$LOCAL_IP --echoOn"
			ssh $USER_NAME@$REMOTE_NODE "$REMCMD"
			if [ $? -ne 0 ]; then
				logError "$USER_NAME@$REMOTE_NODE failed to accept the ssh host key of this node ($LOCAL_IP)"
				break
			fi
			if [ "$USER_NAME" != "root" ]; then
				REMCMD="$SSH_UTILS_SCRIPT --addKnownHost --userName=root --remoteNode=$LOCAL_IP --echoOn"
				ssh root@$REMOTE_NODE "$REMCMD"
				if [ $? -ne 0 ]; then
					logError "root@$REMOTE_NODE failed to accept the ssh host key of this node ($LOCAL_IP)"
					break
				fi
			fi

			# Testing from remote to local
			${LOGIT} Info "Testing ssh connection from remote to local..."
			REMCMD="ssh $SSH_OPT root@$LOCAL_IP uname -a"
			ssh $SSH_OPT $USER_NAME@$REMOTE_NODE "$REMCMD"
			if [ $? -ne 0 ]; then
				logError "Remote ($USER_NAME@$REMOTE_NODE) can't ssh to this local (root@$LOCAL_IP)"
				break
			fi
			ssh $SSH_OPT root@$REMOTE_NODE "$REMCMD"
			if [ $? -ne 0 ]; then
				logError "Remote (root@$REMOTE_NODE) can't ssh to this local (root@$LOCAL_IP)"
				break
			fi
		fi
		rm -f $PASS_FILE

		logSuccess "The local ($LOCAL_IP) is done exchanging ssh keys with remote ($REMOTE_NODE)"
		break;
	done
}

function exchangeKeys() 
{
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	${LOGIT} Info "Executing ./nifi_tls_utils.sh --exchangeKeys --remoteNode=$REMOTE_NODE --userName=$USER_NAME --passWord=********"
	exchangeSshKeys
}

function areNodesPairedRemote() 
{
	# $1 - Local node
	# $2 - Remote node
	# $3 - Web UI Port
	# $4 - User name to login to remote
	# $5 - User password to login to remote
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	# Assume error by default
	setRetVal $ERR_RETVAL

	REMOTE_NODE="$2"
	USER_NAME="$4"
	PASSWD="$5"
	# Make sure both local and remote can ssh to each other both ways
	exchangeSshKeys >/dev/null 2>&1
	if [ $RETVAL -eq $OK_RETVAL ]; then
		${LOGIT} Info "Checking if nodes are paired at remote..."
		REMCMD="/var/www/softnas/scripts/nifi_tls_utils.sh --areNodesPaired --remoteNode=$1 --webUIPort=$3 --localOnly"
		ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS /usr/bin/env TERM=dumb $REMCMD"
		if [ $? -eq 0 ]; then
			logSuccess "Nifi nodes were paired (checked from $REMOTE_NODE)"
		else
			logError "Nifi nodes were not paired (checked from $REMOTE_NODE)"
		fi
	fi
}

function areNodesPairedLocal()
{
	# $1 = Source node
	# $2 = Target node
	# $3 = Web UI port
	# $4 = true/false (check localhost connectivity if true)
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values
	
	# Assume error by default
	setRetVal $ERR_RETVAL

	${LOGIT} Info "Verifying if nifi nodes were already paired..."
	# Verifying local
	local CERT="/var/www/softnas/keys/nifi/$1/buurst.pem"
	canConnect $1 $3 $4
	if [ $RETVAL -eq $OK_RETVAL ]; then
		canAccess $1 $3 $4 $CERT
	fi

	# Verifying remote
	if [ $RETVAL -eq $OK_RETVAL ]; then
		canConnect $2 $3 false
	fi
	if [ $RETVAL -eq $OK_RETVAL ]; then
		CERT="/var/www/softnas/keys/nifi/$2/buurst.pem"
		canAccess $2 $3 false $CERT
		if [ $RETVAL -eq $OK_RETVAL ]; then
			CERT="/var/www/softnas/keys/nifi/$2/server.pem"
			canAccess $2 $3 false $CERT
			if [ $RETVAL -eq $OK_RETVAL ]; then
				CERT="/var/www/softnas/keys/nifi/$1/server.pem"
				canAccess $2 $3 false $CERT
			fi
		fi
	fi

	# Logging result
	if [ $RETVAL -eq $OK_RETVAL ]; then
		logSuccess "Nifi nodes were paired (checked from localhost)"
	else
		logError "Nifi nodes were not paired (checked from localhost)"
	fi
}

function areNodesPaired() 
{
	# $1 = Source node
	# $2 = Target node
	# $3 = Web UI port
	# $4 = true/false (check localhost connectivity if true)
	# $5 = User name to login to target
	# $6 = Password to login to target
	# $7 = true/false (Check from local to remote only)
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	# Assume error by default
	setRetVal $ERR_RETVAL

	MSG="Executing ./nifi_tls_utils.sh --areNodesPaired --remoteNode=$2 --webUIPort=$3 --userName=$5 \
--passWord=********"
	[ "$7" == "true" ] && MSG="$MSG --localOnly"
	${LOGIT} Info "$MSG"

	# Check if nodes are paired via local instance
	areNodesPairedLocal $1 $2 $3 $4
	if [ $RETVAL -eq $OK_RETVAL ]; then
		if [ ! "$7" = true ]; then
			# Check if nodes are paired via remote instance
			areNodesPairedRemote $1 $2 $3 $5 $6
		else
			REMOTE_NODE="$2"
			USER_NAME="$5"
			PASSWD="$6"
			# Make sure local can ssh to remote only
			exchangeSshKeys true >/dev/null 2>&1
		fi
	fi
}

function setNifiMonitoring()
{	
	# $1 = true/false - enable or disable monitoring nifi
	if [ "$1" = true ]; then
		monit monitor NiFi
		monit monitor NiFiRegistry
	else
		monit unmonitor NiFi
		monit unmonitor NiFiRegistry
	fi
}

function checkUser()
{
	# $1 = Nifi node
	# $2 = Web ui port
	# $3 = User CN
	# $4 = Certificate path
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	# Assume error by default
	setRetVal $ERR_RETVAL
	local VALUE="CN=$3, O=Buurst, L=Houston, ST=TX, C=US"
	local ENCODED_VALUE=$(python -c "import urllib.parse; print(urllib.parse.quote('''$VALUE'''))")
	local SEARCHED=$(curl -s -k --cert "$4" https://$1:$2/nifi-api/tenants/search-results?q=$ENCODED_VALUE | grep "$3")
	if [ ! -z "$SEARCHED" ]; then
		local USER_UUID=$(echo "$SEARCHED" | php -r '$json=json_decode(fgets(STDIN)); echo json_encode($json->users[0]->id);' | sed -e 's/"//g')
		if [ ! -z "$USER_UUID" ]; then
			logSuccess "$USER_UUID"
		fi
	fi
}

function addNifiUser()
{
	# $1 = Nifi node
	# $2 = Web ui port
	# $3 = User CN
	# $4 = Certificate path
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values.
	# 		If successful, RETMSG is the user UUID

	while : ; do
		# Assume error by default
		setRetVal $ERR_RETVAL
		# If user already exists, don't add it
		checkUser "$1" "$2" "$3" "$4"
		if [ $RETVAL -eq $OK_RETVAL ]; then
			logSuccess "$RETMSG"
			break
		fi
		cat << EOF > /tmp/.input.json
{
  "revision": {
    "version": 0
  },
  "permissions": {
    "canRead": true,
    "canWrite": true
  },
  "component": {
    "identity": "CN=$3, O=Buurst, L=Houston, ST=TX, C=US"
  }
}
EOF
		local RESPONSE=$(curl -s -i -k \
			 -H "Accept: application/json" \
			 -H "Content-Type:application/json" \
			 -X POST \
			 --data "$(cat /tmp/.input.json)" \
			 --cert "$4" \
			 https://$1:$2/nifi-api/tenants/users | grep "$3")
		if [ -z "$RESPONSE" ]; then
			logError "Failed in adding user with CN=$3 to nifi node $1"
			break
		fi
		local USER_UUID=$(echo "$RESPONSE" | php -r '$json=json_decode(fgets(STDIN)); echo json_encode($json->component->id);' | sed -e 's/"//g')
		logSuccess "$USER_UUID"
		break
	done
}

function checkUserPolicy()
{
	# $1 - Nifi node
	# $2 - Web ui port
	# $3 - User CN
	# $4 - Certificate path
	# $5 - Action (read/write)
	# $6 - Resource
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	# Assume error by default
	setRetVal $ERR_RETVAL
	local POLICIES=$(curl -s -k --cert $4 https://$1:$2/nifi-api/policies/$5/$6 | grep "/$6")
	local USERS=$(echo "$POLICIES" |  php -r '$json=json_decode(fgets(STDIN)); echo json_encode($json->component->users);' | python -m json.tool)
	if echo "$USERS" | grep -q "CN=$3"; then
		logSuccess "User with CN=$3 has already $5 policy to resource $6"
	fi
}

function addUserPolicy()
{
	# $1 - Nifi node
	# $2 - Web ui port
	# $3 - User CN
	# $4 - User UUID
	# $5 - Certificate path
	# $6 - Action (read/write)
	# $7 - Resource
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	while : ; do
		# Assume error by default
		setRetVal $ERR_RETVAL
		checkUserPolicy "$1" "$2" "$3" "$5" "$6" "$7"
		if [ $RETVAL -eq $OK_RETVAL ]; then
			logSuccess "User with CN=$3 has already $6 access policy to resouce $7 of node $1"
			break
		fi
		local URI="https://$1:$2/nifi-api/policies"
		local METHOD="POST"
		local POLICIES=$(curl -s -k --cert $5 https://$1:$2/nifi-api/policies/$6/$7 | grep "/$7")
		local USERS=$(echo "$POLICIES" |  php -r '$json=json_decode(fgets(STDIN)); echo json_encode($json->component->users);' | python -m json.tool)
		if [ ! "$USERS" = "null" ]; then
			METHOD="PUT"
			URI=$(echo "$POLICIES" |  php -r '$json=json_decode(fgets(STDIN)); echo json_encode($json->uri);' | python -mjson.tool | sed -e 's/"//g')
			local REVISION=$(echo "$POLICIES" |  php -r '$json=json_decode(fgets(STDIN)); echo json_encode($json->revision);' | python -m json.tool)
			local COMPID=$(echo "$POLICIES" |  php -r '$json=json_decode(fgets(STDIN)); echo json_encode($json->component->id);' | python -mjson.tool)
			if [ ! "$USERS" = "[]" ]; then
				echo "$USERS" | sed -e 's/]/,/g' > /tmp/.users.json
			else
				echo '[' > /tmp/.users.json
			fi
			cat << EOF >> /tmp/.users.json
	{
	    "id": "$4",
	    "permissions": {
	      "canRead": true,
	      "canWrite": true
	    },
	    "component": {
	      "identity": "CN=$3, O=Buurst, L=Houston, ST=TX, C=US",
	      "id": "$4"
	    },
	    "revision": {
	      "version": 0
	    }
	}
]
EOF
			cat << EOF > /tmp/.policy.json
{
  "revision" : $REVISION,
  "component":
    {
      "id": $COMPID,
      "users": $(cat /tmp/.users.json)
    }
}
EOF
		else
			cat << EOF > /tmp/.policy.json
{
  "component": {
    "resource": "/$7",
    "users": [
      {
        "id": "$4",
        "permissions": {
          "canRead": true,
          "canWrite": true
        },
        "component": {
          "identity": "CN=$3, O=Buurst, L=Houston, ST=TX, C=US",
          "id": "$4"
        },
        "revision": {
          "version": 0
        }
      }
    ],
    "action": "$6"
  },
  "revision": {
    "version": 0
  }
}
EOF
		fi
		curl -i -s -k \
			-H "Accept: application/json" \
			-H "Content-Type:application/json" \
			-X $METHOD \
			--data "$(cat /tmp/.policy.json)" \
			--cert $5 $URI
		if [ $? -ne 0 ]; then
			logError "Failed in adding $6 access policy of user with CN=$3 to resouce $7 of node $1"
			break
		fi
		logSuccess "Successfully added $6 access policy of user with CN=$3 to resource $7 of node $1"
		break
	done
}

function setAuthorizers()
{
	# $1 - home directory

	cat << EOF > "$1/conf/authorizers.xml"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<authorizers>
    <authorizer>
        <identifier>file-provider</identifier>
        <class>org.apache.nifi.authorization.FileAuthorizer</class>
        <property name="Authorizations File">./conf/authorizations.xml</property>
        <property name="Users File">./conf/users.xml</property>
        <property name="Initial Admin Identity">CN=buurst, O=Buurst, L=Houston, ST=TX, C=US</property>
        <property name="Legacy Authorized Users File"></property>
    </authorizer>
</authorizers>
EOF
}

function setRegistryAuthorizers(){
	# $1 - home directory
	# $2 - admin user name
	# $3 - advertised ip

	rm -f $1/nifi-registry/conf/users.xml
	rm -f $1/nifi-registry/conf/authorizations.xml
	cat << EOF > "$1/nifi-registry/conf/authorizers.xml"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<authorizers>
    <userGroupProvider>
        <identifier>file-user-group-provider</identifier>
        <class>org.apache.nifi.registry.security.authorization.file.FileUserGroupProvider</class>
        <property name="Users File">./conf/users.xml</property>
        <property name="Initial User Identity 1">CN=buurst, O=Buurst, L=Houston, ST=TX, C=US</property>
        <property name="Initial User Identity 2">CN=$2, O=Buurst, L=Houston, ST=TX, C=US</property>
        <property name="Initial User Identity 3">CN=$3, O=Buurst, L=Houston, ST=TX, C=US</property>
    </userGroupProvider>
    <accessPolicyProvider>
        <identifier>file-access-policy-provider</identifier>
        <class>org.apache.nifi.registry.security.authorization.file.FileAccessPolicyProvider</class>
        <property name="User Group Provider">file-user-group-provider</property>
        <property name="Authorizations File">./conf/authorizations.xml</property>
        <property name="Initial Admin Identity">CN=buurst, O=Buurst, L=Houston, ST=TX, C=US</property>
        <property name="NiFi Group Name"></property>
        <property name="NiFi Identity 1">CN=$3, O=Buurst, L=Houston, ST=TX, C=US</property>
    </accessPolicyProvider>
    <authorizer>
        <identifier>managed-authorizer</identifier>
        <class>org.apache.nifi.registry.security.authorization.StandardManagedAuthorizer</class>
        <property name="Access Policy Provider">file-access-policy-provider</property>
    </authorizer>
</authorizers>
EOF
}

function addDefaultUserGlobalPolicies()
{
	# $1 - NiFi node
	# $2 - Web ui port
	# $3 - User CN
	# $4 - User UUID
	# #5 - Home directory
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	while : ; do
		# Assume error by default
		setRetVal $ERR_RETVAL
		local CERT="$5/ssl/user-buurst/buurst.pem"
		addUserPolicy "$1" "$2" "$3" "$4" "$CERT" "read" "policies"
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
		addUserPolicy "$1" "$2" "$3" "$4" "$CERT" "write" "policies"
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
		addUserPolicy "$1" "$2" "$3" "$4" "$CERT" "read" "tenants"
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
		addUserPolicy "$1" "$2" "$3" "$4" "$CERT" "write" "tenants"
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
		addUserPolicy "$1" "$2" "$3" "$4" "$CERT" "read" "controller"
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
		addUserPolicy "$1" "$2" "$3" "$4" "$CERT" "write" "controller"
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
		addUserPolicy "$1" "$2" "$3" "$4" "$CERT" "read" "flow"
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
		addUserPolicy "$1" "$2" "$3" "$4" "$CERT" "read" "system"
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
		addUserPolicy "$1" "$2" "$3" "$4" "$CERT" "write" "restricted-components"
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
		addUserPolicy "$1" "$2" "$3" "$4" "$CERT" "read" "provenance"
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
		addUserPolicy "$1" "$2" "$3" "$4" "$CERT" "write" "proxy"
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
		addUserPolicy "$1" "$2" "$3" "$4" "$CERT" "read" "counters"
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
		addUserPolicy "$1" "$2" "$3" "$4" "$CERT" "write" "counters"
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
		logSuccess "Successfully added default user global policies"
		break
	done
}

function addDefaultUsersGlobalPolicies()
{
	# $1 - Advertised IP
	# $2 - Web ui port
	# $3 - UUID of user buurst
	# $4 - UUID of user admin
	# $5 - Alias of user admin
	# $6 - Home directory
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	# Assume error by default
	setRetVal $ERR_RETVAL
	addDefaultUserGlobalPolicies $1 $2 buurst $3 "$6"
	if [ $RETVAL -eq $OK_RETVAL ]; then
		logSuccess "Successfully added default users global policies"
	fi
}

function deleteUnusedTemplates()
{
	# $1 - Nifi host
	# $2 - Nifi web ui port
	# $3 - Path to user certificate

	template_names=(\
		"Fuusion Many-to-One Source Template"\
		"Fuusion Many-to-One Target Template"\
		"Fuusion One-to-Many Source Template"\
		"Fuusion One-to-Many Target Template"\
	)
	setRetVal $OK_RETVAL
	local URI_PREFIX="https://$1:$2/nifi-api"
	local TEMPLATES=$(curl -s -k --cert $3 $URI_PREFIX/flow/templates)

	for i in "${!template_names[@]}"; do
		local URI=$( echo "$TEMPLATES" | jq --arg template "${template_names[i]}" '.templates[] | select(.template.name=='"\"${template_names[i]}\""') | .template.uri' | tr -d \'\" )
		[ -z "$URI" ] && continue
		if ! curl -k --cert "$3" -X "DELETE" $URI; then
			logError "Failed in removing template ${template_names[i]}"
		fi
	done
}

function importNifiTemplate() 
{
	# $1 - Nifi host
	# $2 - Nifi web ui port
	# $3 - Path to user certificate
	# $4 - Nifi api endpoint
	# $5 - Path to template xml file
	# $6 - Search id

	setRetVal $OK_RETVAL
	local URI_PREFIX="https://$1:$2/nifi-api"
	local TEMPLATES=$(curl -s -k --cert $3 $URI_PREFIX/flow/templates)
	if ! echo "$TEMPLATES" | grep -q "$6"; then
		local RESULT=$(curl -s -k -X POST -F template=@"$5" --cert $3 $URI_PREFIX/$4)
		if ! echo "$RESULT" | grep -q "$6"; then
			logError "Failed in adding template $5"
		fi
	fi
}

function importNifiTemplates()
{
	# $1 - Nifi host
	# $2 - Nifi web ui port
	# $3 - Path to user certificate
	# $4 - Nifi api endpoint

	template_files=(\
		"shore-to-ships-v1.xml"\
		"ship-side-ingest-v2.xml"\
	)

	template_names=(\
		"shore-to-ships-v1"\
		"ship-side-ingest-v2"\
	)

	for i in "${!template_files[@]}"; do
		# upload template
		local TEMPLATE="/var/www/softnas/templates/${template_files[i]}"
		importNifiTemplate $1 $2 $3 $4 "$TEMPLATE" "${template_names[i]}"
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
	done
	deleteUnusedTemplates $1 $2 "$3"
	if [ $RETVAL -eq $OK_RETVAL ]; then
		logSuccess "Added all nifi templates successfully"
	fi
}

function addDefaultUsersRootPolicies()
{
	# $1 - Advertised ip
	# $2 - Web ui port
	# $3 - UUID of user buurst
	# $4 - UUID of user admin
	# $5 - Alias of user admin
	# $6 - Home directory
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	while : ; do
		# Assume error by default
		setRetVal $ERR_RETVAL
		local CERT="$6/ssl/user-buurst/buurst.pem"
		local ROOTPG_UUID=$(curl -s -k --cert $CERT \
			https://$1:$2/nifi-api/flow/process-groups/root | \
			php -r '$json=json_decode(fgets(STDIN)); echo json_encode($json->processGroupFlow->id);' | sed -e 's/"//g')
		if [ $? -eq 0 ] && [ ! -z "$ROOTPG_UUID" ] && [ ! "$ROOTPG_UUID" = "null" ]; then
			addUserPolicy "$1" "$2" "buurst" "$3" "$CERT" "read" "process-groups/$ROOTPG_UUID"
			if [ $RETVAL -ne $OK_RETVAL ]; then
				break
			fi
			addUserPolicy "$1" "$2" "buurst" "$3" "$CERT" "read" "data/process-groups/$ROOTPG_UUID"
			if [ $RETVAL -ne $OK_RETVAL ]; then
				break
			fi
			addUserPolicy "$1" "$2" "buurst" "$3" "$CERT" "read" "policies/process-groups/$ROOTPG_UUID"
			if [ $RETVAL -ne $OK_RETVAL ]; then
				break
			fi
			addUserPolicy "$1" "$2" "buurst" "$3" "$CERT" "write" "process-groups/$ROOTPG_UUID"
			if [ $RETVAL -ne $OK_RETVAL ]; then
				break
			fi
			addUserPolicy "$1" "$2" "buurst" "$3" "$CERT" "write" "data/process-groups/$ROOTPG_UUID"
			if [ $RETVAL -ne $OK_RETVAL ]; then
				break
			fi
			addUserPolicy "$1" "$2" "buurst" "$3" "$CERT" "write" "policies/process-groups/$ROOTPG_UUID"
			if [ $RETVAL -ne $OK_RETVAL ]; then
				break
			fi
			importNifiTemplates "$1" "$2" "$CERT" "process-groups/$ROOTPG_UUID/templates/upload"
			if [ $RETVAL -ne $OK_RETVAL ]; then
				break
			fi
			logSuccess "Successfully added admin access policies to default users"
		fi
		break;
	done
}

function addSiteToSitePolicies()
{
	# $1 - Local node
	# $2 - Local node's web ui port
	# $3 - Remote node
	# $4 - Remote node's web ui port
	# #5 - Home directory
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	while : ; do
		# Assume error by default
		setRetVal $ERR_RETVAL
		local CERT="$5/ssl/user-buurst/buurst.pem"
		addNifiUser 127.0.0.1 $2 $3 $CERT
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
		addUserPolicy 127.0.0.1 $2 $3 $RETMSG $CERT "read" "site-to-site"
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
		CERT="/var/www/buurst/keys/nifi/$3/buurst.pem"
		addNifiUser $3 $4 $1 $CERT
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
		addUserPolicy $3 $4 $1 $RETMSG $CERT "read" "site-to-site"
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
		logSuccess "Successfully added site-to-site policies"
		break
	done
}

function checkIfOkToExecute()
{
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values
	local SETUP_TIME=$NIFI_UPTIME_SECS
	local CUR_TIME=$(date +%s)
	local START_TIME=0
	local PREV_PID=0
	local ERROR=true
	local ERR_MSG=""

	setRetVal $ERR_RETVAL
	if [ -f /tmp/.nifi-tls-utils-setup.flag ]; then
		PREV_PID=$(cat /tmp/.nifi-tls-utils-setup.flag | awk '{ print $1 }')
		START_TIME=$(cat /tmp/.nifi-tls-utils-setup.flag | awk '{ print $2 }')
		ERR_MSG="There is an ongoing process to configure NiFi for secure SSL access"
	elif [ -f /tmp/.nifi-tls-utils-exchange.flag ]; then
		PREV_PID=$(cat /tmp/.nifi-tls-utils-exchange.flag | awk '{ print $1 }')
		START_TIME=$(cat /tmp/.nifi-tls-utils-exchange.flag | awk '{ print $2 }')
		ERR_MSG="There is an ongoing process to exchange certs/keys with a remote node"
	elif [ -f /tmp/.nifi-tls-utils-migrate.flag ]; then
		PREV_PID=$(cat /tmp/.nifi-tls-utils-migrate.flag | awk '{ print $1 }')
		START_TIME=$(cat /tmp/.nifi-tls-utils-migrate.flag | awk '{ print $2 }')
		ERR_MSG="There is an ongoing process to migrate nifi home directory"
	else
		ERROR=false
	fi

	if [ "$ERROR" = true ]; then
		if [ $PREV_PID -gt 0 ] &&  [ ! "$(kill -0 $PREV_PID;echo $?)" = "0" ]; then
			rm -f /tmp/.nifi-tls-utils-*.flag
			logSuccess "Previous process is dead. Forcefully removing flags"
		else
			local REM_TIME_SEC=$((START_TIME + SETUP_TIME - CUR_TIME))
			local REM_TIME_MIN=$(python -c "from math import ceil; print(ceil($REM_TIME_SEC/60.0))")
			local MORE_MSG="."
			if (( $(echo "$REM_TIME_MIN > 0" | bc -l) )); then
				MORE_MSG=" (after about $REM_TIME_MIN minute/s)."
			fi
			logError "$ERR_MSG.</br>Please try again later$MORE_MSG"
		fi
	else
		logSuccess "Ok to execute script"
	fi
}

function getTimeoutMsg()
{
	# $1 - host
	# $2 - port
	# $3 - (true/false --> is host local?)
	# $4 - timeout time

	local ISLOCAL="remote"
	if [ "$3" = true ]; then
		ISLOCAL="local"
	fi
	echo "Timeout in connecting to $ISLOCAL $1:$2 for $4.</br>Please check firewall or network security group settings."
}

function setupAuth()
{
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	local LOCALHOST_IP="127.0.0.1"
	local DELETE_FLAG=true
	while : ; do
		# Check if there are pending conflicting processes
		checkIfOkToExecute
		if [ $RETVAL -ne $OK_RETVAL ]; then
			DELETE_FLAG=false
			break
		fi
		echo "$PID $(date +%s)" > /tmp/.nifi-tls-utils-setup.flag

		# Assume error by default
		setRetVal $ERR_RETVAL

		# Check if nifi is installed or not
		dpkg -l | grep nifi | grep -w "ii" -q
		if [ $? -ne 0 ]; then
			logError "No NiFi package installed"
			break
		fi

		REL_SSL_DIR=".\/ssl"
		NIFI_PROPS="$HOME_DIR/conf/nifi.properties"
		TMP_IPS="/tmp/.tmp_server_ips.txt"
		CHCONF_SCRIPT="/var/www/softnas/scripts/update_properties.sh"

		# Query all IPs - public and private
		getAllIPs $TMP_IPS
		getSANExts $TMP_IPS
		rm -f $TMP_IPS

		${LOGIT} Info "Executing ./nifi_tls_utils.sh --setupAuth --advertisedIP=$ADVERTISED_IP --webUIPort=$WEBUI_PORT --dataPort=$DATA_PORT \
--adminUser=$ADMIN_USER --homeDir=$HOME_DIR --restartNifi=$RESTART_NIFI"

		# Check if nifi is already configured for SSL given the user-specified options
		local KEYSTORE=`grep nifi.security.keystore= $HOME_DIR/conf/nifi.properties | awk -F "=" '{print $2}'`
		local TIMEOUT_SETUPCHECK=100 # Timeout in seconds
		local TIMEOUT_UPCHECK=$NIFI_UPTIME_SECS # Timeout in seconds
		local RETRY_INTERVAL=20      # Retry inverval in seconds

		logUpdate "Verifying if Nifi server was configured for secure SSL access..."
		if [ ! -z "$KEYSTORE" ] && [ ! "$NO_PRECHECK" = true ]; then
			isNodeSetup $ADVERTISED_IP $WEBUI_PORT $DATA_PORT true $ADMIN_USER $TIMEOUT_SETUPCHECK $RETRY_INTERVAL
		else
			${LOGIT} Info "Nifi is not yet configured for SSL - Keystore is not set!"
		fi
		local BUURST_UUID=""
		local ADMIN_UUID=""
		# Only configure Nifi if all checks above failed
		if [ $RETVAL -eq $OK_RETVAL ]; then
			# Update admin policies
			local CERT="$SSL_DIR/user-buurst/buurst.pem"
			addNifiUser $LOCALHOST_IP $WEBUI_PORT buurst "$CERT"
			if [ $RETVAL -eq $OK_RETVAL ]; then
				BUURST_UUID="$RETMSG"
				addNifiUser $LOCALHOST_IP $WEBUI_PORT $ADMIN_USER "$CERT"
				if [ $RETVAL -eq $OK_RETVAL ]; then
					ADMIN_UUID="$RETMSG"
					# Update global and root policies for default users
					addDefaultUsersRootPolicies $LOCALHOST_IP $WEBUI_PORT $BUURST_UUID $ADMIN_UUID $ADMIN_USER $HOME_DIR
					addDefaultUsersGlobalPolicies $LOCALHOST_IP $WEBUI_PORT $BUURST_UUID $ADMIN_UUID $ADMIN_USER $HOME_DIR
				fi
			fi
			addNifiUser $LOCALHOST_IP $WEBUI_PORT $ADVERTISED_IP "$CERT"
			logUpdate "Nifi server was already configured."
		else
			logUpdate "Verification failed. Configuring Nifi server..."
			mkdir -p $SSL_DIR
			# Get current settings
			getProperties
			if [ -f $SSL_DIR/.passwd ]; then
				PASS=`cat $SSL_DIR/.passwd`
			fi
			echo $PASS > $SSL_DIR/.passwd
			if [ "$CUR_IP" != "$ADVERTISED_IP" ] || [ ! -f $SSL_DIR/keystore.jks ]; then
				# Create server keystore in jks format
				rm -f $SSL_DIR/keystore.jks
				${LOGIT} Info "Creating server keystore..."
				keytool -genkeypair -keyalg RSA -keysize 2048 -validity 3650 -keystore $SSL_DIR/keystore.jks -alias self -keypass $PASS \
					-storepass $PASS $SAN_EXTS -ext BC=ca:false -ext KU=nonRepudiation,digitalSignature,keyEncipherment -ext EKU=serverAuth,clientAuth \
					-dname CN=$ADVERTISED_IP\,O=Buurst\,L=Houston\,ST=TX\,C=US

				# Export server cert into server.crt
				if [ -f $SSL_DIR/keystore.jks ]; then
					rm -f $SSL_DIR/server.crt
					${LOGIT} Info "Exporting server certificate from keystore..."
					keytool -exportcert -keystore $SSL_DIR/keystore.jks -keypass $PASS -storepass $PASS -alias self -file $SSL_DIR/server.crt -rfc
					if [ ! -f $SSL_DIR/server.crt ]; then
						logError "Failed to generate certificate for this node"
						break
					fi
				else
					logError "Failed to create keystore for this node"
					break
				fi
			fi
			# Create server's key and cert in PEM format
			if [ -f $SSL_DIR/keystore.jks ]; then
				rm -f $SSL_DIR/server.pem
				${LOGIT} Info "Creating server's key and cert in PEM format..."
				keytool -importkeystore -srckeystore $SSL_DIR/keystore.jks -destkeystore $SSL_DIR/keystore.p12 -srcstoretype JKS -deststoretype PKCS12 \
					-srcstorepass $PASS -deststorepass $PASS -srcalias self -destalias self -srckeypass $PASS -destkeypass $PASS -noprompt
				openssl pkcs12 -in $SSL_DIR/keystore.p12 -out $SSL_DIR/keystore.key.pem -nocerts -nodes -passin file:$SSL_DIR/.passwd
				openssl pkcs12 -in $SSL_DIR/keystore.p12 -out $SSL_DIR/keystore.crt.pem -clcerts -nokeys -passin file:$SSL_DIR/.passwd
				openssl rsa -in $SSL_DIR/keystore.key.pem -out $SSL_DIR/server.key.pem
				cp $SSL_DIR/keystore.crt.pem $SSL_DIR/server.pem
				cat $SSL_DIR/server.key.pem >> $SSL_DIR/server.pem
				chmod 664 $SSL_DIR/server.pem
				rm -f $SSL_DIR/keystore.p12 $SSL_DIR/server.key.pem $SSL_DIR/keystore.key.pem $SSL_DIR/keystore.crt.pem
			else
				logError "Failed to create keystore for this node"
				break
			fi

			# Create a truststore in jks format
			if [ -f $SSL_DIR/truststore.jks ]; then
				keytool -list -keystore $SSL_DIR/truststore.jks -storepass $PASS | grep self
				if [ $? -eq 0 ]; then
					keytool -delete -alias self -keystore $SSL_DIR/truststore.jks -storepass $PASS -noprompt
				fi
			fi
			${LOGIT} Info "Creating truststore (if not existing) and importing self cert into it..."
			keytool -importcert -trustcacerts -alias self -file $SSL_DIR/server.crt -keystore $SSL_DIR/truststore.jks -storepass $PASS -noprompt
			if [ $? -ne 0 ]; then
				logError "Failed to create truststore for this node"
				break;
			fi

			# Configure site-to-site settings
			cp $HOME_DIR/conf/nifi.properties $HOME_DIR/conf/nifi.properties.bk
			${LOGIT} Info "Configuring nifi site-to-site..."
			$CHCONF_SCRIPT "$HOME_DIR/conf/nifi.properties" \
				nifi.remote.input.socket.port=$DATA_PORT \
				nifi.remote.input.secure=true \
				nifi.remote.input.host=$ADVERTISED_IP

			# Configure web properties
			${LOGIT} Info "Configuring nifi web properties..."
			$CHCONF_SCRIPT "$HOME_DIR/conf/nifi.properties" \
				nifi.web.https.host=$ADVERTISED_IP \
				nifi.web.https.port=$WEBUI_PORT \
				nifi.security.keystore="$REL_SSL_DIR\/keystore.jks" \
				nifi.security.keystoreType=jks \
				nifi.security.keystorePasswd=$PASS \
				nifi.security.keyPasswd=$PASS \
				nifi.security.truststore="$REL_SSL_DIR\/truststore.jks" \
				nifi.security.truststoreType=jks \
				nifi.security.truststorePasswd=$PASS \
				nifi.security.needClientAuth=true \
				nifi.security.user.authorizer=file-provider

			# Configure nifi-registry
			${LOGIT} Info "Configuring nifi-registry properties..."
			cp $HOME_DIR/nifi-registry/conf/nifi-registry.properties $HOME_DIR/nifi-registry/conf/nifi-registry.properties.bk
			$CHCONF_SCRIPT "$HOME_DIR/nifi-registry/conf/nifi-registry.properties" \
				nifi.registry.web.https.port=18443 \
				nifi.registry.security.keystorePasswd=$PASS \
				nifi.registry.security.keyPasswd=$PASS \
				nifi.registry.security.truststorePasswd=$PASS

			# Create default users
			# Delete old user cert
			keytool -list -keystore $SSL_DIR/truststore.jks -storepass $PASS | grep softnas
			if [ $? -eq 0 ]; then
				keytool -delete -alias softnas -keystore $SSL_DIR/truststore.jks -storepass $PASS -noprompt
			fi
			${LOGIT} Info "Creating default users..."
			USER_DIR="$SSL_DIR/user-buurst"
			if [ ! -d $USER_DIR ]; then
				createUser buurst $PASS
			fi
			keytool -list -keystore $SSL_DIR/truststore.jks -storepass $PASS | grep buurst
			if [ $? -eq 0 ]; then
				keytool -delete -alias buurst -keystore $SSL_DIR/truststore.jks -storepass $PASS -noprompt
			fi
			keytool -importcert -trustcacerts -alias buurst -file $USER_DIR/buurst.crt -keystore $SSL_DIR/truststore.jks -storepass $PASS --noprompt
			if [ -f $SSL_DIR/.admin_user.txt ]; then
				local PREV_ADMIN=`cat $SSL_DIR/.admin_user.txt`
				if [ "$PREV_ADMIN" != "$ADMIN_USER" ]; then
					rm -rf $SSL_DIR/user-$PREV_ADMIN
					keytool -list -keystore $SSL_DIR/truststore.jks -storepass $PASS | grep $PREV_ADMIN
					if [ $? -eq 0 ]; then
						keytool -delete -alias $PREV_ADMIN -keystore $SSL_DIR/truststore.jks -storepass $PASS -noprompt
					fi
				fi
			fi
			USER_DIR="$SSL_DIR/user-$ADMIN_USER"
			if [ ! -d $USER_DIR ]; then
				createUser $ADMIN_USER $PASS
				echo $ADMIN_USER > $SSL_DIR/.admin_user.txt
			fi
			keytool -list -keystore $SSL_DIR/truststore.jks -storepass $PASS | grep $ADMIN_USER
			if [ $? -eq 0 ]; then
				keytool -delete -alias $ADMIN_USER -keystore $SSL_DIR/truststore.jks -storepass $PASS -noprompt
			fi
			keytool -importcert -trustcacerts -alias $ADMIN_USER -file $USER_DIR/$ADMIN_USER.crt -keystore $SSL_DIR/truststore.jks \
				-storepass $PASS -noprompt

			# Update permissions on generated certs
			chown -R buurst:root $SSL_DIR
			chmod -R 775 $SSL_DIR

			# Set authorizers file and initial admin identity (CN=buurst, ...)
			cp $HOME_DIR/conf/authorizers.xml $HOME_DIR/conf/authorizers.xml.bk
			setAuthorizers "$HOME_DIR"
			setRegistryAuthorizers "$HOME_DIR" $ADMIN_USER $ADVERTISED_IP

			# Copy certs of default user and this server's certificates into /var/www/softnas/keys/<ip>
			local NIFI_KEYS_DIR="/var/www/softnas/keys/nifi"
			rm -rf $NIFI_KEYS_DIR/$ADVERTISED_IP
			mkdir -p $NIFI_KEYS_DIR/$ADVERTISED_IP
			cp -f $SSL_DIR/user-buurst/curl/buurst.pem $NIFI_KEYS_DIR/$ADVERTISED_IP/buurst.pem
			cp -f $SSL_DIR/user-buurst/curl/buurst.key.pem $NIFI_KEYS_DIR/$ADVERTISED_IP/buurst.key.pem
			cp -f $SSL_DIR/server.crt $NIFI_KEYS_DIR/$ADVERTISED_IP/server.crt
			cp -f $SSL_DIR/server.pem $NIFI_KEYS_DIR/$ADVERTISED_IP/server.pem

			# Create ssh keys
			${LOGIT} Info "Setting up ssh keys..."
			/var/www/softnas/scripts/nifi_ssh_utils.sh --createKeys --userName=root --echoOn
			if [ $? -ne 0 ]; then
				logError "Failed to create ssh key pair for root user"
				break
			fi

			# Update /etc/httpd/conf.d/nifi.conf
			syncNiFiConf

			# Restart and wait nifi if specified by caller
			if [ "$RESTART_NIFI" = true ]; then
				logUpdate "Done configuring Nifi server. Restarting Nifi..."

				setNifiMonitoring false
				${LOGIT} Info "Disabling nifi monitoring..."

				# Restart nifi
				/var/www/softnas/scripts/nifi-service.sh restart &

				# Wait until server is up
				${LOGIT} Info "Waiting until server is up (waits for $NIFI_UPTIME_STR)"
				local CERT="$SSL_DIR/user-buurst/curl/buurst.pem"
				isReachable $ADVERTISED_IP $WEBUI_PORT true $TIMEOUT_UPCHECK $RETRY_INTERVAL true "$CERT"
				if [ $RETVAL -ne $OK_RETVAL ]; then
					TIMEOUT_MSG=$(getTimeoutMsg $ADVERTISED_IP $WEBUI_PORT true "$NIFI_UPTIME_STR")
					logError "$TIMEOUT_MSG"
					break
				fi
				isReachable $ADVERTISED_IP $DATA_PORT true $TIMEOUT_UPCHECK $RETRY_INTERVAL false
				if [ $RETVAL -ne $OK_RETVAL ]; then
					TIMEOUT_MSG=$(getTimeoutMsg $ADVERTISED_IP $DATA_PORT true "$NIFI_UPTIME_STR")
					logError "$TIMEOUT_MSG"
					break
				fi
				REGISTRYPORT=$(cat $HOME_DIR/nifi-registry/conf/nifi-registry.properties | grep nifi.registry.web.https.port | awk -F"=" '{ print $2 }')
				isReachable $ADVERTISED_IP $REGISTRYPORT true $TIMEOUT_UPCHECK $RETRY_INTERVAL false
				if [ $RETVAL -ne $OK_RETVAL ]; then
					TIMEOUT_MSG=$(getTimeoutMsg $ADVERTISED_IP $REGISTRYPORT true "$NIFI_UPTIME_STR")
					logError "$TIMEOUT_MSG"
					break
				fi

				# Give default users admin access policies
				${LOGIT} Info "Adding admin access policies to default users..."
				addNifiUser $LOCALHOST_IP $WEBUI_PORT buurst "$CERT"
				if [ $RETVAL -eq $OK_RETVAL ]; then
					BUURST_UUID="$RETMSG"
					addNifiUser $LOCALHOST_IP $WEBUI_PORT $ADMIN_USER "$CERT"
					if [ $RETVAL -eq $OK_RETVAL ]; then
						ADMIN_UUID="$RETMSG"
						# Update global and root policies for default users
						addDefaultUsersRootPolicies $LOCALHOST_IP $WEBUI_PORT $BUURST_UUID $ADMIN_UUID $ADMIN_USER $HOME_DIR
						addDefaultUsersGlobalPolicies $LOCALHOST_IP $WEBUI_PORT $BUURST_UUID $ADMIN_UUID $ADMIN_USER $HOME_DIR
					fi
				fi
				addNifiUser $LOCALHOST_IP $WEBUI_PORT $ADVERTISED_IP "$CERT"
				setNifiMonitoring true
			fi
		fi

		logSuccess "Done setting up nifi authentication"
		break
	done

	if [ "$DELETE_FLAG" = true ]; then
		${LOGIT} Info "Deleting setupAuth() flag file"
		rm -f /tmp/.nifi-tls-utils-setup.flag
	fi
}

function enableSshPassLogin()
{
	# $1 value is either "true" or "false"
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	while : ; do
		# Assume error by default
		setRetVal $ERR_RETVAL

		local LOCK_OPT="lock"
		if [ "$1" == "true" ]; then
			LOCK_OPT="unlock"
		fi
		${LOGIT} Info "Trying to $LOCK_OPT ssh password authentication of $REMOTE_NODE..."
		softnas-cmd login $USER_NAME "$PASSWD" -b https://$REMOTE_NODE/buurst -s 12345 -i -t
		if [ $? -ne 0 ]; then
			logError "Failed to login to $USER_NAME@$REMOTE_NODE"
			break
		fi
		curl -k --cookie /tmp/softnascmd.12345 https://$REMOTE_NODE/buurst/snserver/sshd.php?$LOCK_OPT
		if [ $? -ne 0 ]; then
			logError "Failed to $LOCK_OPT ssh password login at $REMOTE_NODE"
			break
		fi
		rm -f /tmp/softnascmd.12345
		local ED="ed"
		logSuccess "Successfully $LOCK_OPT$ED ssh password login at $REMOTE_NODE"
		break
	done
}

function exchangeCerts()
{
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	local DELETE_FLAG=true
	while : ; do
		# Check if there are pending conflicting processes
		checkIfOkToExecute
		if [ $RETVAL -ne $OK_RETVAL ]; then
			DELETE_FLAG=false
			break
		fi
		echo "$PID $(date +%s)" > /tmp/.nifi-tls-utils-exchange.flag

		# Assume error by default
		setRetVal $ERR_RETVAL

		# Check if nifi is installed or not
		dpkg -l | grep nifi | grep -w "ii" -q
		if [ $? -ne 0 ]; then
			logError "No NiFi package installed locally"
			break
		fi

		local LOCAL_IP=`$GTCONF_SCRIPT -h`
		local WEBUI_PORT=`$GTCONF_SCRIPT -w`
		local DATA_PORT=`$GTCONF_SCRIPT -p`
		local TIMEOUT_UPCHECK=$NIFI_UPTIME_SECS
		local RETRY_INTERVAL=20

		${LOGIT} Info "Executing ./nifi_tls_utils.sh --exchangeCerts --remoteNode=$REMOTE_NODE --userName=$USER_NAME --passWord=******** \
--restartNifi=$RESTART_NIFI"

		logUpdate "Checking if nodes have exchanged certificates and keys..."
		# Exchange certs only if 2 nodes were not yet paired
		# The checking if 2 nodes were paired is from source to target only
		areNodesPaired $LOCAL_IP $REMOTE_NODE $WEBUI_PORT true $USER_NAME $PASSWD true
		if [ $RETVAL -eq $OK_RETVAL ]; then
			# Update site-to-site policies
			addSiteToSitePolicies $LOCAL_IP $WEBUI_PORT $REMOTE_NODE $WEBUI_PORT $HOME_DIR
			if [ $RETVAL -ne $OK_RETVAL ]; then
				break
			fi
			logUpdate "Done exchanging certificates and keys."
		else
			logUpdate "Nodes were not yet paired. Exchanging certificates and keys..."
			exchangeSshKeys true
			if [ $RETVAL -ne $OK_RETVAL ]; then
				break
			fi
			${LOGIT} Info "Checking if this node has public certificate.."
			if [ ! -f $SSL_DIR/server.crt ]; then
				logError "This node ($LOCAL_IP) has no public certificate"
				break
			fi
			REM_HOME_DIR=$(ssh root@$REMOTE_NODE $0 --getNifiHome)
			REM_SSL_DIR="$REM_HOME_DIR/ssl"
			${LOGIT} Info "Getting remote's nifi home: $REM_HOME_DIR"

			${LOGIT} Info "Checking if $REMOTE_NODE has public certificate"
			ssh $USER_NAME@$REMOTE_NODE "test -e $REM_SSL_DIR/server.crt"
			if [ $? -ne 0 ]; then
				logError "Remote node ($REMOTE_NODE) has no public certificate"
				break
			fi

			NIFI_KEYS_DIR="/var/www/softnas/keys/nifi"
			rm -rf $NIFI_KEYS_DIR/$LOCAL_IP
		 	mkdir -p $NIFI_KEYS_DIR/$LOCAL_IP
			cp -f $SSL_DIR/user-buurst/curl/buurst.pem $NIFI_KEYS_DIR/$LOCAL_IP/buurst.pem
			cp -f $SSL_DIR/user-buurst/curl/buurst.key.pem $NIFI_KEYS_DIR/$LOCAL_IP/buurst.key.pem
			cp -f $SSL_DIR/server.crt $NIFI_KEYS_DIR/$LOCAL_IP/server.crt
			cp -f $SSL_DIR/server.pem $NIFI_KEYS_DIR/$LOCAL_IP/server.pem

			${LOGIT} Info "Copying public certificate of $REMOTE_NODE ..."
			rm -rf $NIFI_KEYS_DIR/$REMOTE_NODE
			mkdir -p $NIFI_KEYS_DIR/$REMOTE_NODE
			scp $USER_NAME@$REMOTE_NODE:$REM_SSL_DIR/server.crt /tmp/.remote.crt
			if [ -f /tmp/.remote.crt ]; then
				cp /tmp/.remote.crt $NIFI_KEYS_DIR/$REMOTE_NODE/server.crt
				${LOGIT} Info "Importing public certificate of $REMOTE_NODE into local's truststore..."
				STORE_PASS=`cat $SSL_DIR/.passwd`
				keytool -list -keystore $SSL_DIR/truststore.jks -storepass $STORE_PASS | grep $REMOTE_NODE
				if [ $? -eq 0 ]; then
					keytool -delete -alias $REMOTE_NODE -keystore $SSL_DIR/truststore.jks -storepass $STORE_PASS -noprompt
				fi
				keytool -importcert -trustcacerts -alias $REMOTE_NODE -keystore $SSL_DIR/truststore.jks -storepass $STORE_PASS -file /tmp/.remote.crt -noprompt
				if [ $? -ne 0 ]; then
					mv $SSL_DIR/truststore.jks.bk $SSL_DIR/truststore.jks
					logError "Failed to import public certificate of remote ($REMOTE_NODE) into local's ($LOCAL_IP) truststore"
					break
				fi
				rm -f /tmp/.remote.crt
			else
				logError "Failed to copy the public certificate of remote ($REMOTE_NODE) via scp"
				break
			fi
			scp $USER_NAME@$REMOTE_NODE:$REM_SSL_DIR/server.pem /tmp/.remote.pem
			if [ -f /tmp/.remote.pem ]; then
				mv /tmp/.remote.pem $NIFI_KEYS_DIR/$REMOTE_NODE/server.pem
			else
				logError "Failed to copy the key pair of remote ($REMOTE_NODE) via scp"
				break
			fi
			${LOGIT} Info "Copying the certificate of default 'buurst' user of $REMOTE_NODE via scp..."
			scp $USER_NAME@$REMOTE_NODE:$REM_SSL_DIR/user-buurst/curl/buurst.pem $NIFI_KEYS_DIR/$REMOTE_NODE/buurst.pem
			if [ ! -f $NIFI_KEYS_DIR/$REMOTE_NODE/buurst.pem ]; then
				logError "Failed to copy the certificate of the default 'buurst' user of remote ($REMOTE_NODE) via scp"
				break
			fi
			${LOGIT} Info "Copying local's public certificate to $REMOTE_NODE..."
			scp $SSL_DIR/server.crt $USER_NAME@$REMOTE_NODE:/tmp/.remote.crt
			ssh $USER_NAME@$REMOTE_NODE "test -e /tmp/.remote.crt"
			if [ $? -eq 0 ]; then
				scp $SSL_DIR/server.pem $USER_NAME@$REMOTE_NODE:/tmp/.remote.pem
				ssh $USER_NAME@$REMOTE_NODE "test -e /tmp/.remote.pem"
			fi
			if [ $? -eq 0 ]; then
				${LOGIT} Info "Importing local's public certificate into the truststore of $REMOTE_NODE..."
				REMOTE_PASS=`ssh $USER_NAME@$REMOTE_NODE "cat $REM_SSL_DIR/.passwd"`
				ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS keytool -list -keystore $REM_SSL_DIR/truststore.jks \
					-storepass $REMOTE_PASS | grep $LOCAL_IP"
				if [ $? -eq 0 ]; then
					ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS keytool -delete -alias $LOCAL_IP -keystore $REM_SSL_DIR/truststore.jks \
						-storepass $REMOTE_PASS -noprompt"
				fi
				ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS keytool -importcert -trustcacerts -alias $LOCAL_IP -keystore \
						$REM_SSL_DIR/truststore.jks -storepass $REMOTE_PASS -file /tmp/.remote.crt -noprompt"
				if [ $? -ne 0 ]; then
					logError "Failed to import the local's ($LOCAL_IP) public certificate into the truststore of remote ($REMOTE_NODE)"
					break
				fi

				ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS rm -rf $NIFI_KEYS_DIR/$LOCAL_IP"
				ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS mkdir -p $NIFI_KEYS_DIR/$LOCAL_IP"
				ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS mv /tmp/.remote.crt $NIFI_KEYS_DIR/$LOCAL_IP/server.crt"
				ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS mv /tmp/.remote.pem $NIFI_KEYS_DIR/$LOCAL_IP/server.pem"

				${LOGIT} Info "Copying the key pair of the default 'buurst' user to $REMOTE_NODE..."
				scp $SSL_DIR/user-buurst/curl/buurst.pem $USER_NAME@$REMOTE_NODE:/tmp/.buurst.pem
				ssh $USER_NAME@$REMOTE_NODE "test -e /tmp/.buurst.pem"
				if [ $? -eq 0 ]; then
					ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS mv /tmp/.buurst.pem $NIFI_KEYS_DIR/$LOCAL_IP/buurst.pem"
				else
					logError "Failed to copy the key pair of the default 'buurst' user to remote ($REMOTE_NODE) via scp"
					break
				fi
				${LOGIT} Info "Copying the public certificate and key pair of $REMOTE_NODE into its /var/www/softnas/keys..."
				ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS rm -rf $NIFI_KEYS_DIR/$REMOTE_NODE"
				ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS mkdir -p $NIFI_KEYS_DIR/$REMOTE_NODE"
				ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS cp -f $REM_SSL_DIR/server.crt $NIFI_KEYS_DIR/$REMOTE_NODE/server.crt"
				ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS cp -f $REM_SSL_DIR/server.pem $NIFI_KEYS_DIR/$REMOTE_NODE/server.pem"
				ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS cp -f $REM_SSL_DIR/user-buurst/curl/buurst.pem $NIFI_KEYS_DIR/$REMOTE_NODE/buurst.pem"
			fi

			# Restart and wait nifi if specified by caller
			if [ "$RESTART_NIFI" = true ]; then
				logUpdate "Done exchanging certificates and keys. Restarting both local and remote Nifi..."

				setNifiMonitoring false
				ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS $0 --monitorNifi --enable=false"
				${LOGIT} Info "Disabling nifi monitoring..."

				${LOGIT} Info "Restarting Nifi of local ($LOCAL_IP)..."
				/var/www/softnas/scripts/nifi-service.sh restart &
				${LOGIT} Info "Restarting Nifi of remote ($REMOTE_NODE)..."
				ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS /var/www/softnas/scripts/nifi-service.sh restart" &
				sleep 5

				# Wait until local server is up
				${LOGIT} Info "Waiting until local ($LOCAL_IP) Nifi is up (waits for $NIFI_UPTIME_STR)"
				local CERT="/var/www/softnas/keys/nifi/$LOCAL_IP/buurst.pem"
				isReachable $LOCAL_IP $WEBUI_PORT true $TIMEOUT_UPCHECK $RETRY_INTERVAL true $CERT
				if [ $RETVAL -ne $OK_RETVAL ]; then
					TIMEOUT_MSG=$(getTimeoutMsg $LOCAL_IP $WEBUI_PORT true "$NIFI_UPTIME_STR")
					logError "$TIMEOUT_MSG"
					break
				fi
				isReachable $LOCAL_IP $DATA_PORT true $TIMEOUT_UPCHECK $RETRY_INTERVAL false
				if [ $RETVAL -ne $OK_RETVAL ]; then
					TIMEOUT_MSG=$(getTimeoutMsg $LOCAL_IP $DATA_PORT true "$NIFI_UPTIME_STR")
					logError "$TIMEOUT_MSG"
					break
				fi

				# Wait until remote server is up
				${LOGIT} Info "Waiting until remote ($REMOTE_NODE) Nifi is up (waits for $NIFI_UPTIME_STR)"
				CERT="/var/www/softnas/keys/nifi/$REMOTE_NODE/buurst.pem"
				isReachable $REMOTE_NODE $WEBUI_PORT false $TIMEOUT_UPCHECK $RETRY_INTERVAL true $CERT
				if [ $RETVAL -ne $OK_RETVAL ]; then
					TIMEOUT_MSG=$(getTimeoutMsg $REMOTE_NODE $WEBUI_PORT false "$NIFI_UPTIME_STR")
					logError "$TIMEOUT_MSG"
					break
				fi
				isReachable $REMOTE_NODE $DATA_PORT false $TIMEOUT_UPCHECK $RETRY_INTERVAL false
				if [ $RETVAL -ne $OK_RETVAL ]; then
					TIMEOUT_MSG=$(getTimeoutMsg $REMOTE_NODE $DATA_PORT false "$NIFI_UPTIME_STR")
					logError "$TIMEOUT_MSG"
					break
				fi

				# Add local as user to remote and vice versa
				addSiteToSitePolicies $LOCAL_IP $WEBUI_PORT $REMOTE_NODE $WEBUI_PORT $HOME_DIR
				if [ $RETVAL -ne $OK_RETVAL ]; then
					logError "$RETMSG"
					break
				fi

				${LOGIT} Info "Enable nifi monitoring..."
				setNifiMonitoring true
				ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS $0 --monitorNifi --enable=true"
			fi
		fi

		logSuccess "Done exchanging server certificates"
		break
	done

	if [ "$DELETE_FLAG" = true ]; then
		${LOGIT} Info "Deleting exchangeCerts() flag file"
		rm -f /tmp/.nifi-tls-utils-exchange.flag
	fi
}

function setupAuthRemote 
{
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	while : ; do
		# Assume error by default
		setRetVal $ERR_RETVAL

		${LOGIT} Info "Executing ./nifi_tls_utils.sh --setupAuthRemote --remoteNode=$REMOTE_NODE --userName=$USER_NAME --passWord=******** \
--advertisedIP=$ADVERTISED_IP --webUIPort=$WEBUI_PORT --dataPort=$DATA_PORT --adminUser=$ADMIN_USER --restartNifi=$RESTART_NIFI"

		exchangeSshKeys true
		if [ $RETVAL -ne $OK_RETVAL ]; then
			break
		fi
		${LOGIT} Info "Setting up SSL authentication at remote..."
		REMCMD="/var/www/softnas/scripts/nifi_tls_utils.sh --setupAuth --advertisedIP=$ADVERTISED_IP --webUIPort=$WEBUI_PORT \
			--dataPort=$DATA_PORT --adminUser=$ADMIN_USER"
		[ -z "$RESTART_NIFI" ] && RESTART_NIFI=false
		[ "$NO_PRECHECK" = true ] && PRECHECK_PARAM="--noPreCheck"
		SSH_OPT=""
		if [ ! -z "$ECHO_ON" ]; then
			SSH_OPT="-v"
			ECHO_ON_PARAM="--echoOn"
		fi
		REMCMD="$REMCMD --restartNifi=$RESTART_NIFI $PRECHECK_PARAM $ECHO_ON_PARAM"
		RESULT=$(ssh $SSH_OPT $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS /usr/bin/env TERM=dumb $REMCMD")
		if [ $? -ne 0 ]; then
			logError "Failed to configure remote ($REMOTE_NODE) for secure SSL access: $RESULT"
			break
		fi
		logSuccess "Done configuring remote ($REMOTE_NODE) for secure SSL access"
		break
	done
}

function getAdminUser() 
{
	local CURRENT_ADMIN="admin"
	if [ -f $SSL_DIR/.admin_user.txt ]; then
		CURRENT_ADMIN=`cat $SSL_DIR/.admin_user.txt`
	fi
	setRetVal $OK_RETVAL "$CURRENT_ADMIN"
}

function migrateNifiHome()
{
	# Required options are HOME_DIR and NEW_HOME_DIR
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	local DELETE_FLAG=true
	while : ; do
		# Check if there are pending conflicting processes
		checkIfOkToExecute
		if [ $RETVAL -ne $OK_RETVAL ]; then
			DELETE_FLAG=false
			break
		fi
		echo "$PID $(date +%s)" > /tmp/.nifi-tls-utils-migrate.flag

		# Assume error by default
		setRetVal $ERR_RETVAL
		${LOGIT} Info "Executing ./nifi_tls_utils.sh --homeDir=$HOME_DIR --newHomeDir=$NEW_HOME_DIR"

		if [ -z "$NEW_HOME_DIR" ] || [ -z "$HOME_DIR" ]; then
			logError "Current/new home directory is not specified"
			break
		fi
		if [ "$NEW_HOME_DIR" = "$HOME_DIR" ]; then
			logError "Current and new repository location is the same. Nothing to do!"
			break
		fi
		mkdir -p "$NEW_HOME_DIR"
		if [ ! -d "$NEW_HOME_DIR" ]; then
			logError "New repository location does not exist and can't be created"
			break
		fi

		setNifiMonitoring false
		${LOGIT} Info "Disabling nifi monitoring..."

		# Stop nifi first
		${LOGIT} Info "Stopping nifi..."
		/var/www/softnas/scripts/nifi-service.sh stop

		${LOGIT} Info "Moving files from current home dir to new home dir..."
		rm -rf $NEW_HOME_DIR/*
		local CMD="rsync -tpogslr $HOME_DIR/ $NEW_HOME_DIR/"
		${LOGIT} Info "Cmd: $CMD"
		if ! $CMD; then
			logError "Failed to move files from current home dir to new home dir"
			break
		fi

		# Copy certs from curl to user dir
		local ADMIN="admin"
		if [ -f $HOME_DIR/ssl/.admin_user.txt ]; then
			ADMIN=`cat $HOME_DIR/ssl/.admin_user.txt`
		fi
		pushd "$NEW_HOME_DIR/ssl/user-$ADMIN"
		rm -f $ADMIN.pem
		cp -f ./curl/$ADMIN.pem ./$ADMIN.pem
		popd
		pushd "$NEW_HOME_DIR/ssl/user-buurst"
		rm -f buurst.pem
		cp -f ./curl/buurst.pem ./buurst.pem
		popd

		# Copy certificates to /var/www/softnas/keys/nifi/<advertised ip>
		local ADVERTISED_IP=`$GTCONF_SCRIPT -h`
		local KEYS_PATH="/var/www/softnas/keys/nifi/$ADVERTISED_IP"
		rm -f $KEYS_PATH/*
		mkdir -p $KEYS_PATH
		cp -f $NEW_HOME_DIR/ssl/user-buurst/buurst.pem $KEYS_PATH/buurst.pem
		cp -f $NEW_HOME_DIR/ssl/user-buurst/curl/buurst.key.pem $KEYS_PATH/buurst.key.pem
		cp -f $NEW_HOME_DIR/ssl/server.pem $KEYS_PATH/server.pem
		cp -f $NEW_HOME_DIR/ssl/server.crt $KEYS_PATH/server.crt

		${LOGIT} Info "Updating nifi home references in some config files..."
		local ESC_HOME_DIR=$(echo $HOME_DIR | sed 's_/_\\/_g')
		local ESC_NEW_HOME_DIR=$(echo $NEW_HOME_DIR | sed 's_/_\\/_g')
		# Update the nifi home variable in /etc/init.d/nifi
		sed -i -e "s/NIFI_HOME=.*/NIFI_HOME=$ESC_NEW_HOME_DIR/g" /etc/init.d/nifi
		# Update the nifi registry home variable in /etc/init.d/nifi-registry
		sed -i -e "s/NIFI_REGISTRY_HOME=.*/NIFI_REGISTRY_HOME=$ESC_NEW_HOME_DIR\/nifi-registry/g" /etc/init.d/nifi-registry
		# Update /etc/nginx/conf.d/nifi.conf.nginx
		syncNiFiConf
		# Update monit config
		/var/www/softnas/scripts/config-generator-monit.sh

		# Remove old nifi home
		${LOGIT} Info "Removing files under old nifi home..."
		rm -rf $HOME_DIR
		touch $NEW_HOME_DIR/conf/.migrated

		# Start nifi from new location
		${LOGIT} Info "Starting nifi from new location.."
		/var/www/softnas/scripts/nifi-service.sh start 

		# Wait until server is up
		local WEBUI_PORT=`$GTCONF_SCRIPT -w`
		local DATA_PORT=`$GTCONF_SCRIPT -p`
		local TIMEOUT_UPCHECK=$NIFI_UPTIME_SECS
		local RETRY_INTERVAL=20
		local CERT="$NEW_HOME_DIR/ssl/user-buurst/curl/buurst.pem"
		${LOGIT} Info "Waiting until server is up (waits for $NIFI_UPTIME_STR)"
		isReachable $ADVERTISED_IP $WEBUI_PORT true $TIMEOUT_UPCHECK $RETRY_INTERVAL true $CERT
		if [ $RETVAL -ne $OK_RETVAL ]; then
			TIMEOUT_MSG=$(getTimeoutMsg $ADVERTISED_IP $WEBUI_PORT true "$NIFI_UPTIME_STR")
			logError "$TIMEOUT_MSG"
			break
		fi
		isReachable $ADVERTISED_IP $DATA_PORT true $TIMEOUT_UPCHECK $RETRY_INTERVAL false
		if [ $RETVAL -ne $OK_RETVAL ]; then
			TIMEOUT_MSG=$(getTimeoutMsg $ADVERTISED_IP $DATA_PORT true "$NIFI_UPTIME_STR")
			logError "$TIMEOUT_MSG"
			break
		fi

		${LOGIT} Info "Enabling nifi monitoring if enabled previously..."
		setNifiMonitoring true

		# restart monit to regenerate updated config with updated nifi home
		service monit restart

		logSuccess "Done migrating nifi home"
		break
	done

	if [ "$DELETE_FLAG" = true ]; then
		${LOGIT} Info "Deleting migrateNifiHome() flag file"
		rm -f /tmp/.nifi-tls-utils-migrate.flag
	fi
}

function checkNifi()
{
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	local ERR_CODE=0
	local LOCAL_IP=`$GTCONF_SCRIPT -h`
	local IS_LOCAL_NODE=false
	local REMLOCAL="Remote"

	while : ; do
		# Assume error by default
		setRetVal $ERR_RETVAL
		${LOGIT} Info "Executing ./nifi_tls_utils.sh --checkNifi --remoteNode=$REMOTE_NODE --webUIPort=$WEBUI_PORT --dataPort=$DATA_PORT \
--userName=$USER_NAME --passWord=******** --restartNifi=$RESTART_NIFI"

		${LOGIT} Debug "Checking if NiFi services at $REMOTE_NODE is accessible..."
		local CERT="/var/www/softnas/keys/nifi/$REMOTE_NODE/buurst.pem"
		if [ "$REMOTE_NODE" = "127.0.0.1" ] || [ "$REMOTE_NODE" = "localhost" ] || [ "$REMOTE_NODE" = "$LOCAL_IP" ]; then
			IS_LOCAL_NODE=true
			REMLOCAL="Local"
			REMOTE_NODE="127.0.0.1"
			CERT="/var/www/softnas/keys/nifi/$LOCAL_IP/buurst.pem"
		fi

		if [ "$IS_LOCAL_NODE" = false ]; then
			${LOGIT} Debug "Checking if server $REMOTE_NODE is up..."
			ssh -o PubkeyAuthentication=yes  -o PasswordAuthentication=no -o ConnectTimeout=3 root@$REMOTE_NODE uname -a
			if [ $? -ne 0 ]; then
				ERR_CODE=1
				logError "$REMLOCAL NiFi server $REMOTE_NODE is unreachable"
				break
			fi
		fi
		canAccess $REMOTE_NODE $WEBUI_PORT false $CERT
		if [ $RETVAL -ne $OK_RETVAL ]; then
			ERR_CODE=2
			logError "$REMLOCAL NiFi service is stopped (Can't reach $REMOTE_NODE:$WEBUI_PORT)"
			break
		fi
		if [ "$IS_LOCAL_NODE" = false ]; then
			CERT="/var/www/softnas/keys/nifi/$LOCAL_IP/server.pem"
			canAccess $REMOTE_NODE $WEBUI_PORT false $CERT
			if [ $RETVAL -ne $OK_RETVAL ]; then
				ERR_CODE=2
				logError "Failed to login to remote NiFi at $REMOTE_NODE using local certificate: Local IP might have changed."
				break
			fi
		fi
		logSuccess "$REMLOCAL NiFi service at $REMOTE_NODE is reachable"
		break
	done

	while : ; do
		if [ $ERR_CODE -eq 2 ] && [ "$RESTART_NIFI" = true ]; then
			getSnapRole
			if [ $RETVAL -eq 0 ] && [ "$RETMSG" = "target" ]; then
				logError "NiFi service at $REMOTE_NODE is stopped since instance is a SnapReplicate/SnapHA secondary node"
				break;
			fi
			${LOGIT} Info "Restarting NiFi service at $REMOTE_NODE ..."
			[ "$ECHO_ON" = true ] && SSH_OPT="-v" || SSH_OPT=""
			local RESULT=""
			local RESCMD="/var/www/softnas/scripts/nifi-service.sh restart"
			if [ "$IS_LOCAL_NODE" = true ]; then
				setNifiMonitoring false
				RESULT=$($RESCMD)
			else
				ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS $0 --monitorNifi --enable=false"
				RESULT=$(ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS /usr/bin/env TERM=dumb $RESCMD")
			fi
			if [ $? -ne 0 ]; then
				logError "Failed to restart NiFi at $REMOTE_NODE: $RESULT"
				break
			fi
			${LOGIT} Info "Waiting until NiFi is up at $REMOTE_NODE ..."
			local WEBUI_PORT=`$GTCONF_SCRIPT -w`
			local DATA_PORT=`$GTCONF_SCRIPT -p`
			local TIMEOUT_UPCHECK=$NIFI_UPTIME_SECS
			local RETRY_INTERVAL=20
			${LOGIT} Info "Waiting until server is up (waits for $NIFI_UPTIME_STR)"
			isReachable $REMOTE_NODE $WEBUI_PORT $IS_LOCAL_NODE $TIMEOUT_UPCHECK $RETRY_INTERVAL true $CERT
			if [ $RETVAL -ne $OK_RETVAL ]; then
				TIMEOUT_MSG=$(getTimeoutMsg $REMOTE_NODE $WEBUI_PORT $IS_LOCAL_NODE "$NIFI_UPTIME_STR")
				logError "$TIMEOUT_MSG"
				break
			fi
			if [ "$IS_LOCAL_NODE" = true ]; then
				setNifiMonitoring true
			else
				ssh $USER_NAME@$REMOTE_NODE "echo '$PASSWD' | sudo -kS $0 --monitorNifi --enable=true"
			fi
			logSuccess "$REMLOCAL NiFi service at $REMOTE_NODE is reachable"
		fi
		break
	done
}

function waitNifi()
{
	# Note: Upon return, sets RETVAL and RETMSG with appropriate values

	while : ; do
		${LOGIT} Info "Executing ./nifi_tls_utils.sh --waitNifi"

		/var/www/softnas/scripts/nifi-service.sh status
		if [ $? -ne 0 ]; then
			# Check if there are pending conflicting processes
			checkIfOkToExecute
			if [ $RETVAL -eq $OK_RETVAL ]; then
				RESTART_NIFI=true
			fi
		fi
		if [ "$RESTART_NIFI" = true ]; then
			/var/www/softnas/scripts/nifi-service.sh restart
		fi

		# Enable certs settings in nifi.conf if disabled
		if grep -qE '[#]SSLProxyCACertificateFile|[#]SSLProxyMachineCertificateFile' /etc/httpd/conf.d/nifi.conf; then
			syncNiFiConf
		fi
		# Assume error by default
		setRetVal $ERR_RETVAL
		${LOGIT} Info "Waiting until NiFi is up..."
		local LOCAL_IP=`$GTCONF_SCRIPT -h`
		local WEBUI_PORT=`$GTCONF_SCRIPT -w`
		local DATA_PORT=`$GTCONF_SCRIPT -p`
		local TIMEOUT_UPCHECK=$NIFI_UPTIME_SECS
		local RETRY_INTERVAL=20
		${LOGIT} Info "Waiting until server is up (waits for $NIFI_UPTIME_STR)"
		local CERT=/var/www/softnas/keys/nifi/$LOCAL_IP/buurst.pem
		isReachable $LOCAL_IP $WEBUI_PORT true $TIMEOUT_UPCHECK $RETRY_INTERVAL true $CERT
		if [ $RETVAL -ne $OK_RETVAL ]; then
			TIMEOUT_MSG=$(getTimeoutMsg $LOCAL_IP $WEBUI_PORT true "$NIFI_UPTIME_STR")
			logError "$TIMEOUT_MSG"
			break
		fi
		REGISTRYPORT=$(cat $HOME_DIR/nifi-registry/conf/nifi-registry.properties | grep nifi.registry.web.https.port | awk -F"=" '{ print $2 }')
		isReachable $LOCAL_IP $REGISTRYPORT true $TIMEOUT_UPCHECK $RETRY_INTERVAL false
		if [ $RETVAL -ne $OK_RETVAL ]; then
			TIMEOUT_MSG=$(getTimeoutMsg $LOCAL_IP $REGISTRYPORT true "$NIFI_UPTIME_STR")
			logError "$TIMEOUT_MSG"
			break
		fi
		logSuccess "NiFi service at this local $LOCAL_IP is up and running"
		break
	done
}

function getSnapRole()
{
    # Note: Upon return, sets RETVAL and RETMSG with appropriate values

    while : ; do
        ${LOGIT} Info "Executing ./nifi_tls_utils.sh --getSnapRole --remoteNode=$REMOTE_NODE --userName=$USER_NAME --passWord=********"

        local IS_LOCAL_NODE=false
        local LOCAL_IP=`$GTCONF_SCRIPT -h`
        if [ "$REMOTE_NODE" = "$LOCAL_IP" ] || [ "$REMOTE_NODE" = "127.0.0.1" ] || [ "$REMOTE_NODE" = "localhost" ]; then
            IS_LOCAL_NODE=true
        fi
        local SNAPINI="`dirname $0`/../config/snaprepstatus.ini"
        local CMD="crudini --get $SNAPINI Relationship1 Role"
        local RESULT=""
        if [ "$IS_LOCAL_NODE" = true ]; then
            RESULT=$($CMD | tr -d \'\")
        else
            ssh -o PubkeyAuthentication=yes -o PasswordAuthentication=no root@$REMOTE_NODE "uname -a"
            if [ $? -ne 0 ]; then
                exchangeSshKeys true
                if [ $RETVAL -ne $OK_RETVAL ]; then
                    break
                fi
            fi
            RESULT=$(ssh root@$REMOTE_NODE "$CMD" | tr -d \'\")
        fi
        setRetVal $OK_RETVAL "$RESULT"
        break
    done
}

function currentHome()
{
	local CURRENT_HOME="/opt/nifidev-$nifidev_version/nifi-$nifi_version"
	if [ -f /etc/init.d/nifi ]; then
		CURRENT_HOME=$(grep NIFI_HOME= /etc/init.d/nifi | awk -F "=" '{print $2}')
	fi
	echo $CURRENT_HOME
}

function getNifiHome()
{
	local CURRENT_HOME=$(currentHome)
	setRetVal $OK_RETVAL "$CURRENT_HOME"
}

##############################################################
# Main decision starts here                                  #
##############################################################
# Generate a random password
PASS=`date | md5sum | awk '{ print $1 }'`
# Set nifi home directory
if [ -z "$HOME_DIR" ] && [ -f /etc/init.d/nifi ]; then
	HOME_DIR=$(currentHome)
fi
if [ -z "$HOME_DIR" ]; then
	HOME_DIR="/opt/nifidev-$nifidev_version/nifi-$nifi_version"
fi
SSL_DIR="$HOME_DIR/ssl"
GTCONF_SCRIPT="/var/www/softnas/scripts/getnificonf.sh"
SAN_EXTS=""
FUNC=""

if [ "$SETUP_AUTH" = true ]; then
	FUNC="setupAuth"
elif [ "$SETUP_AUTH_REM" = true ]; then
	FUNC="setupAuthRemote"
elif [ "$CREATE_USER" = true ]; then
	FUNC="createUser $USER_NAME $PASS"
elif [ "$EXCHANGE_CERTS" = true ]; then
	FUNC="exchangeCerts"
elif [ "$SYNC_NIFI_CONF" = true ]; then
	FUNC="syncNiFiConf"
elif [ "$SSH_PASS_LOGIN" = true ]; then
	FUNC="enableSshPassLogin $ENABLE"
elif [ "$GET_ADMIN_USER" = true ]; then
	FUNC="getAdminUser"
elif [ "$ARE_NODES_PAIRED" = true ]; then
	LOCAL_IP=`$GTCONF_SCRIPT -h`
	[ -z "$REMOTE_NODE" ] && REMOTE_NODE="127.0.0.1"
	[ -z "$WEBUI_PORT" ] && WEBUI_PORT="9443"
	[ -z "$USER_NAME" ] && USER_NAME="buurst"
	[ -z "$PASSWD" ] && PASSWD="Pass4W0rd"
	[ -z "$LOCAL_ONLY" ] && LOCAL_ONLY="false"
	FUNC="areNodesPaired $LOCAL_IP $REMOTE_NODE $WEBUI_PORT true $USER_NAME $PASSWD $LOCAL_ONLY"
elif [ "$EXCHANGE_KEYS" = true ]; then
	FUNC="exchangeKeys"
elif [ "$MIGRATE_NIFI_HOME" = true ]; then
	FUNC="migrateNifiHome"
elif [ "$GET_NIFI_HOME" = true ]; then
	FUNC="getNifiHome"
elif [ "$MONITOR_NIFI" = true ]; then
	FUNC="setNifiMonitoring $ENABLE"
elif [ "$CHECK_PENDING" = true ]; then
	FUNC="checkIfOkToExecute"
elif [ "$CHECK_NIFI" = true ]; then
	FUNC="checkNifi"
elif [ "$WAIT_NIFI" = true ]; then
	FUNC="waitNifi"
elif [ "$GET_SNAP_ROLE" = true ]; then
	FUNC="getSnapRole"
else
	logError "Invalid options specified!"
	exit 1
fi

# Redirect output to /dev/null if specified
if [ ! "$ECHO_ON" = true ]; then
	$FUNC > /dev/null 2>&1
else
	$FUNC
fi

echo $RETMSG
exit $RETVAL

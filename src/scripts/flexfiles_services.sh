#!/bin/bash

PROD_PATH="/var/www/softnas"
source $PROD_PATH/scripts/logger.sh
logger_initLogging "$PROD_PATH/logs/flexfiles.log"
LOGIT="logger_log"

if [ "$1" = "enable" ]; then
	MONIT_ON_OFF="on"
	systemctl start ultrafast
elif [ "$1" = "disable" ]; then
	MONIT_ON_OFF="off"
else
	echo "FlexFiles services: Invalid command"
	exit 1
fi

sed -i "s/MONITOR_NIFI=.*/MONITOR_NIFI=\"$MONIT_ON_OFF\"/g" /var/www/softnas/config/monitoring.ini
sed -i "s/MONITOR_ULTRAFAST=.*/MONITOR_ULTRAFAST=\"$MONIT_ON_OFF\"/g" /var/www/softnas/config/monitoring.ini
systemctl restart monit  >/dev/null 2>&1

if [ "$1" = "disable" ]; then
	service monit restart  >/dev/null 2>&1
	$LOGIT Info "FlexFiles services: Stopping services..."
	systemctl start ultrafast  >/dev/null 2>&1
	/var/www/softnas/scripts/nifi-service.sh stop  >/dev/null 2>&1
else
	# Wait until NiFi is installed
	CTR=0
	RETRIES=30
	while [ $CTR -lt $RETRIES ] ; do
		if [ ! -f /opt/.nifi_installed.flag ]; then
			CTR=$((CTR+1))
			sleep 10
			$LOGIT Info "FlexFiles services: Retry $CTR of $RETRIES. Waiting until NiFi is installed..."
		else
			break
		fi
	done
	# Setup NiFi to use SSL if not yet configured
	$LOGIT Info "FlexFiles services: Setting up nifi for secured SSL access..."
	GETCONF_SCRIPT="/var/www/softnas/scripts/getnificonf.sh"
	NIFI_TLS_SCRIPT="/var/www/softnas/scripts/nifi_tls_utils.sh"
	ADVERTISED_IP=`$GETCONF_SCRIPT -h`
	WEBUI_PORT=`$GETCONF_SCRIPT -w`
	DATA_PORT=`$GETCONF_SCRIPT -p`
	ADMIN_USER=`$NIFI_TLS_SCRIPT --getAdminUser`
	PRIVATE_IPS=`ip -f inet addr show | grep " *inet " | grep -v " lo$" | awk '{ sub (/\/.*/,""); print $2 }' | tr '\n' ' '`
	PUBLIC_IP=`curl -k https://softnas.com/ip.php 2>/dev/null`
	NIFI_HOME=$(/var/www/softnas/scripts/nifi_tls_utils.sh --getNifiHome)
	/var/www/softnas/scripts/nifi_custom_props.sh $NIFI_HOME
	if ! echo "$PRIVATE_IPS $PUBLIC_IP" | grep -q "\b$ADVERTISED_IP\b"; then
		ADVERTISED_IP=`echo "$PRIVATE_IPS" | awk '{ print $1 }'`
	fi
	$NIFI_TLS_SCRIPT --setupAuth --advertisedIP=$ADVERTISED_IP --webUIPort=$WEBUI_PORT --dataPort=$DATA_PORT --adminUser=$ADMIN_USER --restartNifi=true --noPreCheck
	$NIFI_TLS_SCRIPT --syncNiFiConf
fi

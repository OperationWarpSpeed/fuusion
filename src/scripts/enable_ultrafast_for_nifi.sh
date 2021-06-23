#!/bin/bash
# Parameter:
#    $1 - enable/disable

PROD_PATH="/var/www/softnas"
PHP="/usr/bin/php"
LOGIT="$PHP $PROD_PATH/snserver/log-it.php flexfiles.log"
SCRIPTDIR="$PROD_PATH/scripts"
NIFI_HOME=$(crudini --get /etc/init.d/nifi "" NIFI_HOME 2>/dev/null | tr -d \'\")
USE_ULTRAFAST=$(crudini --get $NIFI_HOME/conf/bootstrap.conf "" use.ultrafast 2>/dev/null  | tr -d \'\")
NIFI_PID=$(ps aux | grep [o]rg.apache.nifi.NiFi | awk '{print $2}')
CONFIG="/usr/local/fuusion/conf/bootstrap.conf"

if [ "$1" = "enable" ] && [ "$USE_ULTRAFAST" != "true" ]; then
	$SCRIPTDIR/update_properties.sh $CONFIG use.ultrafast=true
elif [ "$1" = "disable" ] && [ "$USE_ULTRAFAST" = "true" ]; then
	$SCRIPTDIR/update_properties.sh $CONFIG use.ultrafast=false
elif [ ! -z "$NIFI_PID" ]; then
	echo "Nothing to do"
	exit 1
fi

# restart nifi now
monit unmonitor NiFi
monit unmonitor NiFiRegistry
# restart and wait for NiFi and NiFi Registry services to be up
$SCRIPTDIR/nifi_tls_utils.sh --restartNifi=true --waitNifi
monit monitor NiFi
monit monitor NiFiRegistry

$LOGIT Info "Done UltraFast $1 for NiFi"

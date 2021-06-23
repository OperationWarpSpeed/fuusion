#!/bin/bash
# Copyright (c) 2013-2016 SoftNAS LLC
#
#


SCRIPTNAME=`basename $0`
SCRIPTPATH=$(dirname $(readlink -f $0))
SCRIPTFILE=$SCRIPTPATH/$SCRIPTNAME

PROD_PATH="/var/www/softnas"
source $PROD_PATH/scripts/logger.sh
logger_initLogging "$PROD_PATH/logs/flexfiles.log"
LOGIT="logger_log"

SCRIPT_PATH="$PROD_PATH/scripts"
CONFIG_FOLDER="$PROD_PATH/config"
LOG_FOLDER="$PROD_PATH/logs"
NIFI_HOME=$($SCRIPT_PATH/nifi_tls_utils.sh --getNifiHome)
NIFI_CONF="$NIFI_HOME/conf/nifi.properties"

#############################################################

if [ -z $1 ]; then
    grep nifi.remote.input.host $NIFI_CONF
    grep nifi.remote.input.socket.port $NIFI_CONF
    grep nifi.remote.input.secure $NIFI_CONF
    grep nifi.web.https.port $NIFI_CONF
fi

if [ "$1" == "-h" ]; then
    grep nifi.remote.input.host $NIFI_CONF | awk -F "=" '{print $2}'
fi

if [ "$1" == "-p" ]; then
    grep nifi.remote.input.socket.port $NIFI_CONF | awk -F "=" '{print $2}'
fi

if [ "$1" == "-w" ]; then
    grep nifi.web.https.port $NIFI_CONF | awk -F "=" '{print $2}'
fi

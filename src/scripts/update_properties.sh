#!/bin/bash
# Copyright (c) 2013-2016 SoftNAS LLC


SCRIPTNAME=`basename $0`
SCRIPTPATH=$(dirname $(readlink -f $0))
SCRIPTFILE=$SCRIPTPATH/$SCRIPTNAME

PROD_PATH="/var/www/softnas"
source $PROD_PATH/scripts/logger.sh
logger_initLogging "$PROD_PATH/logs/flexfiles.log"
LOGIT="logger_log"

CONFIG_FILE="$1"
shift

#############################################################

if [ -z $1 ]; then
	echo "Usage : $SCRIPTNAME.sh <config file> <key>=<value> <key1>=<value1> ..."
	exit 0
fi

for i; do 
	KEY=$(echo "$i" | awk -F "=" '{print $1}')
	VALUE=$(echo "$i" | awk -F"$KEY=" '{ print $2 }')
	crudini --set $CONFIG_FILE "" $KEY "$VALUE"
done

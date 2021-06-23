#!/bin/bash
# Check whether the instance is azure, vmware or aws

PROD_PATH="/var/www/softnas"
SCRIPT_PATH="$PROD_PATH/scripts"
CONFIG_FOLDER="$PROD_PATH/config"
CONFIG_FILE="$CONFIG_FOLDER/which_host.ini"
LOGIT="php $PROD_PATH/snserver/log-it.php snserv.log"

function old_check()
{

 if curl "http://169.254.169.254/latest/meta-data/instance-id" --connect-timeout 5 -s > /dev/null ; then	
  echo "aws"
  echo aws > $CONFIG_FILE
 elif curl "http://metadata.google.internal/computeMetadata/v1/instance/id" -H "Metadata-Flavor: Google" --connect-timeout 5 -s > /dev/null ; then
  echo "google"
  echo google > $CONFIG_FILE
 elif curl -H Metadata:true --noproxy "*" "http://169.254.169.254/metadata/instance?api-version=2020-09-01" --connect-timeout 5 -s > /dev/null ; then
  echo "azure"
  echo azure > $CONFIG_FILE
 else 
  echo "vmware"
  echo vmware > $CONFIG_FILE
fi
}

if [ -f "$CONFIG_FILE" ]; then
    cat $CONFIG_FILE
else
    old_check
fi


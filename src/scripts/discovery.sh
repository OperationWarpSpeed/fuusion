#!/bin/bash
SCRIPTDIR=/var/www/softnas/scripts

if [[ `$SCRIPTDIR/which_host.sh` == "azure" ]]; then
	#statements
	echo "azure"
  	exit 0
elif [[ `$SCRIPTDIR/which_host.sh` == "aws" ]];
then
  echo "amazon"
  exit 0
elif [[ `$SCRIPTDIR/which_host.sh` == "google" ]];
then
  echo "google"
  exit 0
fi

if [ -b /dev/sda ]; then
  echo "VM"
  exit 0
fi

echo "??"

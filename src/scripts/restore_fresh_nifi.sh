#!/bin/bash
#
# Below are the steps of cleaning up NiFi installation and start fresh. 
# These steps can be done instead of deploying new instances, configuring NiFi home, 
# adding platinum license and enabling platinum features which could take considerable amount of time. 
#
# Here are the steps - https://app.assembla.com/spaces/softnas-llc/wiki/Steps_of_restoring_fresh_NiFi_installation 
# Assumptions: 
# 	- NiFi is already enabled and configured
# 	- NiFi dev and src versions below are used
# 

# Parameters:
#  $1 - [1 or 0] - 1 means restore installation files, 0 means remove all nifi flows only. Default 1

scriptdir="/var/www/softnas/scripts"
source $scriptdir/nifi_version.sh

monit unmonitor NiFi
$scriptdir/nifi-service.sh stop
NIFI_HOME=$($scriptdir/nifi_tls_utils.sh --getNifiHome)

if [ -z "$1" ] || [ "$1" = "1" ]; then
	TEMPDIR="/tmp/.nifi-`date +%s`"
	mkdir -p $TEMPDIR
	cp -Rf $NIFI_HOME/conf/nifi.properties $NIFI_HOME/conf/bootstrap.conf $NIFI_HOME/conf/.migrated $NIFI_HOME/ssl $NIFI_HOME/bin $TEMPDIR/
	rm -rf $NIFI_HOME/*
	tar -xvzf /opt/nifidev-${nifidev_version}/nifi-${nifi_version}.tar.gz -C $NIFI_HOME/
	cp -f $TEMPDIR/nifi.properties $TEMPDIR/bootstrap.conf $TEMPDIR/.migrated $NIFI_HOME/conf/
	cp -Rf $TEMPDIR/ssl $TEMPDIR/bin $NIFI_HOME/
	rm -rf $TEMPDIR
	$scriptdir/install_update_nifi.sh
	$scriptdir/startstop-flexfiles.sh start all 4
else
	rm -rf $NIFI_HOME/*_repository $NIFI_HOME/conf/archive $NIFI_HOME/conf/flow.xml.gz
fi

$scriptdir/nifi_tls_utils.sh --waitNifi
$scriptdir/nifi_tls_utils.sh --setupAuth --restartNifi=true
monit monitor NiFi
echo "Done"

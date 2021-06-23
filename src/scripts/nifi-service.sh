#!/bin/bash

function cleanup()
{
	local PROC_LIST=$(ps -ef | grep '[o]rg.apache.nifi' | awk '{print $2}')
	if [ ! -z "$PROC_LIST" ]; then
		kill $PROC_LIST
	fi
	local NIFI_SCRIPT=$(ps -ef | grep '[b]in/nifi.sh' | awk '{print $2}')
	if [ ! -z "$NIFI_SCRIPT" ]; then
		kill $NIFI_SCRIPT
	fi
	sleep 2
}

if [ ! -f /etc/init.d/nifi ]; then
	echo "No nifi service installed"
	exit 1
fi
if [ ! -f /etc/init.d/nifi-registry ]; then
	echo "No nifi-registry service installed"
	exit 1
fi


if [ "$1" = "start" ]; then
	echo "Starting nifi service..."
	cleanup
	/etc/init.d/nifi-registry start
	/etc/init.d/nifi start
elif [ "$1" = "stop" ]; then
	echo "Stopping nifi service..."
	/etc/init.d/nifi stop
	/etc/init.d/nifi-registry stop
elif [ "$1" = "restart" ]; then
	echo "Restarting nifi service..."
	/etc/init.d/nifi stop
	/etc/init.d/nifi-registry stop
	cleanup
	/etc/init.d/nifi-registry restart
	/etc/init.d/nifi restart
elif [ "$1" = "status" ]; then
	echo "Checking nifi status..."
	STATUS=$(/etc/init.d/nifi status 2>&1)
	if ! echo "$STATUS" | grep -q "Apache NiFi is currently running"; then
		exit 1
	fi
	STATUS=$(/etc/init.d/nifi-registry status 2>&1)
	if ! echo "$STATUS" | grep -q "Apache NiFi Registry is currently running"; then
		exit 1
	fi
elif [ "$1" = "cleanup" ]; then
	echo "Cleaning previous nifi processes"
	cleanup
	ps -ef | grep '[o]rg.apache.nifi'
	ps -ef | grep '[n]ifi.sh'
fi

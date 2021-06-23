#!/bin/bash

if [ -d /etc/hosts.d ]; then
	echo "host file generator: /etc/hosts.d exists"
	echo "host file generator: clear /etc/hosts"
	:> /etc/hosts
	for i in /etc/hosts.d/* ; do
		echo "host file generator: add contents of ${i} to /etc/hosts"
		cat $i >> /etc/hosts
	done
fi

#6116
#Verify hostname exits in /etc/hosts
MYIP=`ip a show eth0 | grep "inet " | awk '{print $2}' | cut -d "/" -f1`

HOSTNAME=`hostname`
if ! grep $HOSTNAME /etc/hosts ; then
    echo "Hostname not found in /etc/hosts. Adding hostname"
    echo "$MYIP $HOSTNAME" >> /etc/hosts
fi

echo "host file generator: complete"
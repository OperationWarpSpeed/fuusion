#!/bin/bash
# rc.startup.sh (formerly initproc.sh) - handles system initialization processing (called from /etc/init.d/softnas)
#
# Copyright 2015 SoftNAS, Inc. - All Rights Reserved
#

SCRIPTDIR=/var/www/softnas/scripts
CONFIGDIR=/var/www/softnas/config
LOGDIR=/var/www/softnas/logs

#3945 - Disable floppy module
rmmod floppy

# 14867 - remove lock files for s3backer
rm -rfv /tmp/*.lock

#18166
systemd-machine-id-setup --root=/

# regenerate /etc/hosts (#4788)
bash ${SCRIPTDIR}/hostfilegenerator.sh

# perform SoftNAS initialization (only executed first time SoftNAS runs)
if [ ! -f "/etc/softnas/firstinit.completed" ]; then
	${SCRIPTDIR}/firstinit.sh &> ${SCRIPTDIR}/firstinit.log
	${SCRIPTDIR}/userinit.sh &> ${LOGDIR}/userinit.log
	touch /etc/softnas/firstinit.completed
else
	logger -t softnas "SoftNAS one-time initialization was previously completed - nothing to do."
fi

# 14969 - comment out nifi certs path which might be invalid - will be fixed in bootinit.sh
NIFI_CONF="/etc/nginx/conf.d/nifi.conf.nginx"
sed -i 's/[^#]\?proxy_ssl/#proxy_ssl/g' $NIFI_CONF
nginx -s reload

# perform regular SoftNAS initialization tasks (executes every time SoftNAS system boots up)
${SCRIPTDIR}/bootinit.sh


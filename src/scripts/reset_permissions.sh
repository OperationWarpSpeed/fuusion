#!/bin/bash

#
# this script will reset permissions during update and reboot
#

export PRODPATH=/var/www/softnas

#14441 - FedRAMP compliance
chown -R root: /root
chmod -R 755 /opt/nifi*
chmod 755 /etc/exports

chown -R root:apache /var/www/softnas/api

# set configuration file permissions #5497
chown -R root:apache /var/www/softnas/config
chmod -R 770 /var/www/softnas/config

find /var/www/softnas/snserver -type d -exec chmod +x \{\} \;

# set configuration file permissions just in case
chown -R root:apache /var/www/softnas/config
chmod -R 770 /var/www/softnas/config
chown -R root:root /var/www/softnas/config/*prototype

# create backup repository (#2595)
mkdir -p /var/www/softnas/backups
chown -R apache: /var/www/softnas/backups

# ensure session temp folder is available and writable
if [ ! -d /tmp/softnas ]; then
  mkdir /tmp/softnas
fi
chmod 777 /tmp/softnas
chown apache:apache /tmp/softnas

# ensure softnas global config folder is present and protected with root-only access
if [ ! -d /etc/softnas ]; then
  mkdir /etc/softnas
fi
chown root:root /etc/softnas
chmod 700 /etc/softnas
chmod 700 /etc/softnas/*

# ensure PHP file permissions disallow write access by webserver and potential attackers
chown root:root $PRODPATH/snserver/*
chmod 744 $PRODPATH/snserver/*

# ensure that all directories in snserver have x
find $PRODPATH/snserver -type d -exec chmod 755 {} \;

# ensure all scripts have x
chmod +x /var/www/softnas/scripts/*.sh /var/www/softnas/scripts/*.py

#5554 - bogus user owns /var/www/softnas/api
chown -R root:apache $PRODPATH/api


# allow webserver read/write access to log and config settings only
[ ! -d $PRODPATH/logs ] && mkdir $PRODPATH/logs
chown root:apache $PRODPATH/logs
chown root:apache $PRODPATH/logs/*
chmod +x $PRODPATH/logs
chmod -R 770 $PRODPATH/logs

# create needed folder + files
mkdir -p $PRODPATH/logs/usage_reports
for i in s3cmd hacmd softnashad ec2-backup; do
  touch $PRODPATH/logs/$i.log
done

# fix logrotate errors
if [ -f /etc/logrotate.d/vsftpd ]; then
  rm /etc/logrotate.d/vsftpd
fi
chown root:root -R /etc/logrotate.d/
chmod 660 $PRODPATH/logs/*
chmod 775 $PRODPATH/logs
touch $PRODPATH/logs/snserv.log
chown root:apache $PRODPATH/logs/snserv.log
chmod 660 $PRODPATH/logs/snserv.log
touch $PRODPATH/logs/flexfiles.log
chown root:apache $PRODPATH/logs/flexfiles.log
chmod 660 $PRODPATH/logs/flexfiles.log
touch $PRODPATH/logs/license.log
chown root:apache $PRODPATH/logs/license.log
chmod 660 $PRODPATH/logs/license.log
touch $PRODPATH/logs/deltasync.log

# remove any leftover log files from snserver directory (they are now in logs directory)
rm -f PRODPATH/snserver/*.log

chown root:apache $PRODPATH/config
chown root:apache $PRODPATH/config/*
chmod 666 $PRODPATH/config/*
chmod 770 $PRODPATH/config

# 15132 - Set appropriate permissions for /usr/bin/sudo
chown root:root /usr/bin /usr/bin/sudo
chmod u+s /usr/bin/sudo

# 18007 - reset registry permissions
NIFI_HOME=$(/var/www/softnas/scripts/nifi_tls_utils.sh --getNifiHome)
chown -R buurst:root $NIFI_HOME
chmod -R 775 $NIFI_HOME
chown -R buurst:root /opt/versioned_flows_repo
chmod -R 775 /opt/versioned_flows_repo

nginx -s reload

#!/bin/bash


## Generates configuration files for monit to place in /etc/monit based on user inputted variables located in /var/www/softnas/config/monitoring.ini

source /var/www/softnas/scripts/nifi_version.sh

# Fix for 1871
HNAME=`hostname`
INTIP=`ifconfig | grep Bcast | awk '{print $2}' | tr -d "addr:" | head -n1`
# End fix for 1871

CONFIG_FILE=/var/www/softnas/config/monitoring.ini
SCRIPTS_DIR=/var/www/softnas/scripts
WHICHHOST=`/var/www/softnas/scripts/which_host.sh`
DEBUG=0

function debug
{
    if [[ "$DEBUG" = "1" ]]; then
        echo $1
    fi
}

## Checks for existence of configuration file, and adds the variables into the script
    if [[ -f $CONFIG_FILE ]]; then
        cat $CONFIG_FILE > "$CONFIG_FILE".tmp
        sed -i 's/;;/#/g' "$CONFIG_FILE".tmp
        . "$CONFIG_FILE".tmp
        debug 'Config file found'
    else
        echo 'Config file not found'
        exit 1
    fi

## Removing old Monit config file
debug 'Backing up existing configuration file to /usr/local/softnas/monit_config_backups'
NOW=$(date +"%Y_%m_%d_%H:%M:%S")
mkdir -p /usr/local/softnas/monit_config_backups/ > /dev/null
mv /etc/monit/conf.d/monit.conf /usr/local/softnas/monit_config_backups/monit.conf.bak.$NOW

## Creating new monit configuration file
touch /etc/monit/conf.d/monit.conf
echo -e '## Monit Configuration file\n## Do not edit manually\n## Edit settings in /var/www/softnas/config/monitoring.ini\n## and run the monit config file generator to update settings\n' >> /etc/monit/conf.d/monit.conf

## Global Monit Settings
echo '## Global section' >> /etc/monit/conf.d/monit.conf
debug 'Setting Monit to check services at' $POLLING_INTERVAL 'second intervals'
echo 'set daemon' $POLLING_INTERVAL >> /etc/monit/conf.d/monit.conf

## Global Settings not editable with monitoring.ini

echo -e 'set logfile /var/log/monit.log\nset httpd port 2812 and\n  allow localhost' >> /etc/monit/conf.d/monit.conf

## Setup email alerts

EMAIL_FROM='softnas@localhost'
if [ "$USE_EXT_SMTP" = "yes" ] && [ "$SMTP_FROM" != "" ]; then
    EMAIL_FROM=$SMTP_FROM
else
    EMAIL_FROM="softnas@`hostname`"
fi

debug 'Notification emails will be sent to' $NOTIFICATION_EMAIL
if [[ "$NOTIFICATION_EMAIL" != "" ]]; then
    echo -e '## Email settings\nset alert' $NOTIFICATION_EMAIL '\n  with mail-format {from:' $EMAIL_FROM'\n subject: SoftNAS Monitoring - $SERVICE $EVENT at $DATE\n    message: SoftNAS Monitoring\n\n$DESCRIPTION\n   Date: $DATE\n   Action: $ACTION\n   Service: $SERVICE\n   HostName: '$HNAME'\n   IP Address : '$INTIP'\n\n\n           Sincerely,\n            SoftNAS Monitoring}' >> /etc/monit/conf.d/monit.conf
fi


## Setup mailserver for email alerts
echo -e '## Mailserver settings' >> /etc/monit/conf.d/monit.conf
if [ "$USE_EXT_SMTP" = "yes" ]
    then
    debug 'Setting up external SMTP server'
    password=$(echo $SMTP_PASSWORD | php -R 'echo base64_decode(preg_quote($argn,"\""));')
    
    if [ "$SMTP_ENCRYPTION" = "none" ]; then
        if [ "$SMTP_USERNAME" = "" ] || [ "$SMTP_PASSWORD" = "" ]; then
            debug 'Setting mailserver without credentials to' $SMTP_MAILSERVER
            echo -e 'set mailserver' $SMTP_MAILSERVER 'port' $SMTP_PORT >> /etc/monit/conf.d/monit.conf
        else
            debug 'Setting mailserver to' $SMTP_MAILSERVER
            echo -e 'set mailserver' $SMTP_MAILSERVER 'port' $SMTP_PORT '\n username "'$SMTP_USERNAME'" password "'`echo $SMTP_PASSWORD | php -R 'echo base64_decode(preg_quote($argn,"\""));'`'"' >> /etc/monit/conf.d/monit.conf
        fi
    else
        if [ "$SMTP_USERNAME" = "" ] || [ "$SMTP_PASSWORD" = "" ]; then
            debug 'Setting mailserver without credentials to' $SMTP_MAILSERVER
            echo -e 'set mailserver' $SMTP_MAILSERVER 'port' $SMTP_PORT '\n using' $SMTP_ENCRYPTION >> /etc/monit/conf.d/monit.conf
        else
            debug 'Setting mailserver to' $SMTP_MAILSERVER
            echo -e 'set mailserver' $SMTP_MAILSERVER 'port' $SMTP_PORT '\n username "'$SMTP_USERNAME'" password "'`echo $SMTP_PASSWORD | php -R 'echo base64_decode(preg_quote($argn,"\""));'`'"\n using' $SMTP_ENCRYPTION >> /etc/monit/conf.d/monit.conf
        fi
    fi  
else
    debug 'Setting localhost as the mailserver'
    echo -e 'set mailserver localhost' >> /etc/monit/conf.d/monit.conf
fi

## Add monitoring of services
echo -e '\n## Services Monitored\n#' >> /etc/monit/conf.d/monit.conf

## sshd service
debug 'sshd monitoring set to' $MONITOR_SSHD
if [ "$MONITOR_SSHD" = "on" ]
    then   
    debug 'Adding ssh service monitoring'
    echo -e '# SSHD_Service\n\n    check process sshd with pidfile /run/sshd.pid\n start program  "/etc/init.d/ssh start"\n   stop program  "/etc/init.d/ssh stop"\n if failed port 22 protocol ssh then restart\n   if 5 restarts within 5 cycles then timeout\n#' >> /etc/monit/conf.d/monit.conf
else   
    debug 'ssh service monitoring disabled in monitoring.ini'
fi

debug 'Adding UltraFast service monitoring'
echo -e '# UltraFast Service
check host UltraFast with address 127.0.0.1\n
   start program = "/opt/ultrafast/bin/ultrafast start"
   stop  program = "/opt/ultrafast/bin/ultrafast stop"
   if failed host 127.0.0.1 port 1080 type tcp with timeout 30 seconds then restart
   if 20 restarts within 20 cycles then timeout' >> /etc/monit/conf.d/monit.conf

echo -e '\n## UltraFast Error Monitoring
check file UltraFast_Errors with path /opt/ultrafast/log/ultrafast.log
        if match "ERROR" then alert' >> /etc/monit/conf.d/monit.conf

## NiFi Service
debug 'Adding NiFi service monitoring'
NIFI_HOME="/usr/local/fuusion"
if [ -f /etc/init.d/nifi ]; then
    NIFI_HOME=`grep 'NIFI_HOME=' /etc/init.d/nifi | awk -F "=" '{print $2}'`
fi
echo -e '\n## NiFi Service
check process NiFi with pidfile '$NIFI_HOME'/run/nifi.pid
   start program = "/var/www/softnas/scripts/nifi-service.sh start"
   stop  program = "/var/www/softnas/scripts/nifi-service.sh stop"' >> /etc/monit/conf.d/monit.conf

if [ -f /etc/init.d/nifi-registry ] ; then   
echo -e '\n## NiFi Registry Service
check process NiFiRegistry with pidfile '$NIFI_HOME'/nifi-registry/run/nifi-registry.pid
   start program = "/etc/init.d/nifi-registry start"
   stop  program = "/etc/init.d/nifi-registry stop"' >> /etc/monit/conf.d/monit.conf
fi
echo -e '\n## NiFi Error Monitoring
check file NiFi_app_Errors with path '$NIFI_HOME'/logs/nifi-app.log
        if match "ERROR" then alert
check file NiFi_boot_Errors with path '$NIFI_HOME'/logs/nifi-bootstrap.log
        if match "ERROR" then alert
check file NiFi_user_Errors with path '$NIFI_HOME'/logs/nifi-user.log
        if match "ERROR" then alert' >> /etc/monit/conf.d/monit.conf

## Includes
#echo -e '\n## Include all files from /etc/monit.d/\n    include /etc/monit.d/*' >> /etc/monit/conf.d/monit.conf

## Verify SSL Common Name
echo -e '\n## Verify SSL Common Name 
check program VerifySSL with path '$SCRIPTS_DIR'/verifyssl.sh 
        if status > 1 then alert' >> /etc/monit/conf.d/monit.conf

## Restart monit
debug 'Restarting monit'

chmod 700 /etc/monit/conf.d/monit.conf
sudo systemctl daemon-reload
if pidof monit ; then
	/etc/init.d/monit restart
else
	/etc/init.d/monit start
fi	

rm -f "$CONFIG_FILE".tmp


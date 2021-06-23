#!/bin/bash
# bootinit.sh - SoftNAS boot initialization script for tasks that run each time system is booted up
#
# Copyright (c) 2013-2015 SoftNAS, Inc. - All Rights Reserved
#
PROD_PATH="/var/www/softnas"
SCRIPTDIR=$PROD_PATH/scripts
CONFIGDIR=$PROD_PATH/config
DEPENDSDIR=$PROD_PATH/dependencies
JAVA="/usr/bin/java -jar"
CURRENT_DATE=`date`
:> /var/log/softnas/boot.status
echo "(${CURRENT_DATE}) Running SoftNAS boot initialization..."

## /tmp stickybit causes permission denied for files written using apache in tmp folder when same file is tried to access via sudo.
if ls -ld /tmp | grep drwxrwxrwt > /dev/null 2>&1 ; then
        sudo chmod -t /tmp
fi

function statuslog()
{
    echo "$@"
    echo "$@" >> /var/log/softnas/boot.status
}

function install_start_nifi() 
{
  /var/www/softnas/scripts/install_update_nifi.sh
  /var/www/softnas/scripts/nifi-service.sh restart 
  /var/www/softnas/scripts/nifi_tls_utils.sh --waitNifi
  NIFI_HOME=$(/var/www/softnas/scripts/nifi_tls_utils.sh --getNifiHome)
  NIFI_HTTPS_HOST=$(cat $NIFI_HOME/conf/nifi.properties | grep nifi.web.https.host | awk -F"=" '{ print $2 }')
  REGISTRYPORT=$(cat $NIFI_HOME/nifi-registry/conf/nifi-registry.properties | grep nifi.registry.web.https.port | awk -F"=" '{ print $2 }')
  php /var/www/softnas/snserver/nifi/nificmd.php --add_registry_client --registry_name "buurst-registry" \
    --registry_url https://$NIFI_HTTPS_HOST:$REGISTRYPORT --registry_desc "Buurst's default registry"
}


# Perform initialization tasks below

statuslog -n "Determine platform type.."
HOST_SERVICE_NAME=`$PROD_PATH/scripts/which_host.sh`
statuslog " ${HOST_SERVICE_NAME}"

# Update ENI Card Name
if [ "$($SCRIPTDIR/which_host.sh)" == "vmware" ]; then
ENINAME=$(lshw -class network | grep -i "logical name" | head -n1 | awk '{print $3}')
 if ! $(grep $ENINAME /etc/netplan/00-network.yaml); then
	echo "network:
  version: 2
  ethernets:
            $ENINAME:
                    dhcp4: true
" > /etc/netplan/00-network.yaml
netplan apply
 fi
fi

#14347
if [ ! -f /etc/ssh/ssh_host_ecdsa_key ] ; then
        ssh-keygen -f /etc/ssh/ssh_host_ecdsa_key -N '' -t ecdsa
fi

if [ ! -f /etc/ssh/ssh_host_rsa_key ] ; then
        ssh-keygen -A
fi
# start/restart sshd
/etc/init.d/ssh restart


statuslog "Ensure configurations are up to date.."
# 14080 - ensure that Azure login command will be called if instance is restarted
if [ "$($SCRIPTDIR/which_host.sh)" == "azure" ]; then
sed -i '/last_logged_in = /d' /var/www/softnas/config/azure.ini 2>/dev/null
fi

# cleanup sessions and verify cache items #
statuslog "Clear old sessions.."
source $CONFIGDIR/sqlpass.conf
/etc/init.d/mysql restart 1> /dev/null
mysql -u storagecenter --password=$USERPASSWORD --execute "DELETE FROM sessions;" storagecenter
rm -rf /tmp/softnas/* 2>/dev/null 1>/dev/null

statuslog "Validate cache.."
if [ -f /var/www/softnas/snserver/cache_validate.php ]; then
	php /var/www/softnas/snserver/cache_validate.php
fi

# enable kernel samepage merging (KSM)
statuslog "Enable KSM.."
echo 1 > /sys/kernel/mm/ksm/run

statuslog "Write clock to RTC in UTC.."
#2403
hwclock --systohc --utc

if [ -f /etc/sysctl.user ]; then
  # 15311 - allow override of sysctls
  sysctl -p /etc/sysctl.user
fi

# Make sure ephemeral disks are not mounted
echo -n "Unmounting ephemeral disks (if found).." >> /var/log/softnas/boot.status
EPHEMERAL_DEV_MOUNTS=$(mount | grep "^/dev/xvd[bcde]" | awk '{print $1}')
if [ -n "${EPHEMERAL_DEV_MOUNTS}" ]; then
	for EPHEMERAL_DEV in ${EPHEMERAL_DEV_MOUNTS}; do
		echo -n "Dismounting ${EPHEMERAL_DEV}..."
    echo -n "..${EPHEMERAL_DEV}.." >> /var/log/softnas/boot.status
		umount ${EPHEMERAL_DEV} || echo "WARNING: Unable to umount ${EPHEMERAL_DEV}"
		sed -i "/\/dev\/$(basename ${EPHEMERAL_DEV})/d" /etc/fstab
	done
fi
echo "... Complete." >> /var/log/softnas/boot.status

if [ -x /opt/ultrafast/bin/ultrafast_bootinit.sh ]; then
  statuslog "Initialize UltraFast service.."
	/opt/ultrafast/bin/ultrafast_bootinit.sh &>/opt/ultrafast/log/bootinit.log
fi

# disable kernel messages overwriting to the console
dmesg -n 1

# Start fcp after all pools are initialized
statuslog "Starting SoftNAS Console.."
$JAVA $SCRIPTDIR/softnas-console-app.jar 2> /dev/null

# workaround of apache first time boot 500 internal error 
/var/www/softnas/scripts/verifyssl.sh # To prevent random failures with Azure

#make sure all fstab entries are mounted
statuslog "Mount fstab entries.."
mount -a

# 3107
if [ -f "/tmp/softnas-rebooting" ]; then
  chmod 666 /tmp/softnas-rebooting
  rm -f /tmp/softnas-rebooting
fi

# this runs after the bootinit.completed so it doesn't hang the rest of the system initialization
SWAPENABLED=$(swapon -s | grep -v Size | wc -l)
if [ "$SWAPENABLED" -eq 0 ]; then
	statuslog "Pre-warming depmod for future updates"
	nohup /sbin/depmod -a `uname -r` &

	if ! [ -f /swap.img ]; then
		statuslog "Generating swap image.. (occurs once)"
		dd if=/dev/zero of=/swap.img bs=1M count=4096
		mkswap /swap.img
	fi
	statuslog "Enable swap file.."
	swapon /swap.img
fi

# Install/update nifi ( #18085 )
statuslog "Install/Configure Apache NiFi "
source /etc/environment
if which java && which keytool ; then
	install_start_nifi 
else
	statuslog "Checking java and keytool - Failed"
	statuslog "Apache NiFi installation skipped ..."
fi

# permissions fixes
statuslog "Resetting permissions.."
/var/www/softnas/scripts/reset_permissions.sh

statuslog "Removing old kernel modules.. (this may take a moment)"
for i in `ls -1 /lib/modules/ | grep -v $(uname -r)` ; do echo $i; rm -rf /lib/modules/$i ; done
for i in `ls -1 /usr/src/kernels/ | grep -v $(uname -r)` ; do echo $i; rm -rf /usr/src/kernels/$i ; done

# 3025 - pending reboot:
chmod 666 /var/www/softnas/config/pendingreboot.* > /dev/null 2>&1
rm -f /var/www/softnas/config/pendingreboot.* > /dev/null 2>&1
## @FIXME
# check for software update with reboot completed
if [ -f /tmp/softnas-update.reboot ]; then
  LOGPATH="/tmp"
  UPDATESTATUS="softnas-update.status"
  chmod 644 $LOGPATH/$UPDATESTATUS
  chown apache:apache $LOGPATH/$UPDATESTATUS
  echo "OK. SoftNAS software update to version `cat /var/www/softnas/version` completed at `date`." > $LOGPATH/$UPDATESTATUS 2>&1
  echo "false" > /tmp/update_in_progress
  rm -f /tmp/softnas-update.reboot
fi


#15781
export AZURE_HTTP_USER_AGENT=$(cat $CONFIGDIR/azure_track_id)

# start/restart monit to regenerate an updated config (/etc/init.d/config)
/var/www/softnas/scripts/config-generator-monit.sh

# Create boot init completion flag
touch /etc/softnas/bootinit.completed

statuslog "Startup complete!"
## End 6568


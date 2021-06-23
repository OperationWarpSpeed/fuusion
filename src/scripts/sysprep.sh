#!/bin/bash
# sysprep.sh - initializes SoftNAS installation back to starting state (for internal and support use only)
#
CONFIGDIR=/var/www/softnas/config
SCRIPTDIR=/var/www/softnas/scripts
HOSTNAME=`hostname`

# Log clear function
clearLogs () {
    for i in `ls $@`; do
        if [[ -w ${i} ]]; then
            echo "Clearing log file ${i}"
            > ${i}
        fi
    done
}

# ensure softnas.ini is reset to starting configuration
echo '[support]' > $CONFIGDIR/softnas.ini
echo 'loglevel = "Info"' >> $CONFIGDIR/softnas.ini
echo 'supportemail="support@softnas.com"' >> $CONFIGDIR/softnas.ini
chown root:apache $CONFIGDIR/softnas.ini
chmod 660 $CONFIGDIR/softnas.ini

echo '[login]' > $CONFIGDIR/login.ini
echo 'timeout="15"' >> $CONFIGDIR/login.ini
echo 'session_folder="/tmp/softnas"' >> $CONFIGDIR/login.ini
echo 'encryption_key="Pass4W0rd"' >> $CONFIGDIR/login.ini
chown root:apache $CONFIGDIR/login.ini
chmod 660 $CONFIGDIR/login.ini

# reset boot-up initialization and any other prior config information
rm -f /etc/softnas/*
#touch /etc/softnas/yumproxy.conf

# remove AWS config (if any)
rm -f $CONFIGDIR/awsconfig.ini

# Remove and clear any old log files
clearLogs /var/www/softnas/logs/*.gz
clearLogs /var/www/softnas/logs/*.log

# remove any prior SoftNAS update folders
rm -rf /var/www/softnas.preupdate

# Remove and clear any old system logs and /tmp
rm -rf /tmp/*
rm -rf /tmp/.*
clearLogs /var/log/*.gz
clearLogs /var/log/auth*
clearLogs /var/log/alternatives*
clearLogs /var/log/bootstrap.*
clearLogs /var/log/mail*
clearLogs /var/log/cloud*
clearLogs /var/log/dmesg*
clearLogs /var/log/faillog*
clearLogs /var/log/kern*
clearLogs /var/log/monit*
clearLogs /var/log/mysql/*
clearLogs /var/log/php*.log.*
clearLogs /var/log/syslog*
clearLogs /var/log/wtmp*
clearLogs /var/log/nginx/*
clearLogs /var/log/php-fpm/*
clearLogs /var/log/samba/*
clearLogs /var/log/flexnet_usage.log

# remove old kernels
#sudo apt-get autopurge -y

# remove all old messages
echo 'd *' | mail -N

# Perform EC2-specific initialization
# On EC2, we ship image without any default passwords
if [ `$SCRIPTDIR/which_host.sh` == "aws" ];
then
# remove any old SSH keys from ec2-user .ssh/authorized_keys by emptying the file
cat > /home/ubuntu/.ssh/authorized_keys </dev/null
# remove any passwords from password shadow file
cp /etc/shadow /etc/shadow.BAK
# remove any existing passwords from the shadow file
awk ' BEGIN{FS=OFS=":"} 
{ for (i=1; i<=NF;i++) 
    if( i==2 && substr( $2 , 0 , 3 )=="$6$" ) printf("*:");
    else printf("%s%s", $i,(i!=NF) ? OFS : ORS)
} ' /etc/shadow.BAK > /etc/shadow
#rm -f /etc/shadow.BAK
fi

# ensure no authorized_keys or known_hosts files are lingering with old credentials (from prior ssh logins)
rm -f /root/.ssh/*
for i in /home/* ; do rm -f $i/.ssh/* ; done

# regenerate ssh host key at boot time
rm -rf /etc/ssh/*key*


# reset NFS threads
sed -i 's/RPCNFSDCOUNT.*/RPCNFSDCOUNT=8/g' /etc/default/nfs-kernel-server

# reset softnas configuration files
cp /var/www/softnas/config/monitoring.ini.prototype      /var/www/softnas/config/monitoring.ini

# clear any shell history
rm -f /home/ubuntu/.bash_history
rm -f /home/softnas/.bash_history
rm -f /root/.bash_history
rm -f /home/buurst/.bash_history
# Remove password for user softnas. We use buurst as user"
usermod -p "*" softnas

# clear abrt logs
rm -rf /var/spool/abrt/*

# remove installation leftovers from old updates
( cd /root/; rm -rf SoftNAS_SP2* zfs* sernet-samba*.tar.gz repos index.html* copytree.* installtree )

# reset samba config
cp /etc/samba/smb.conf.softnas /etc/samba/smb.conf

if [ `$SCRIPTDIR/which_host.sh` == "vmware" ]; then
# fix network devices on vmware
rm -f /etc/udev/rules.d/70-persistent-net.rules
touch /etc/udev/rules.d/70-persistent-net.rules

#Remove machine-id to avoid same IP address and should be regenerated during firstboot
rm -f /etc/machine-id
rm -f /var/lib/dbus/machine-id

# Set default password as buurst
echo "buurst:buurst" | chpasswd
fi

# create /etc/hosts file
cat > /etc/hosts << EOF
127.0.0.1 localhost
::1 localhost
EOF
rm -f /etc/hostname && touch /etc/hostname

# 17497 - Remove old ultrafast config
rm -f /opt/ultrafast/conf/*

# Remove NiFi and reinstall during startup
/etc/init.d/nifi stop
/etc/init.d/nifi-registry stop
rm -f /opt/.nifi_installed.flag
rm -f /etc/init.d/nifi /etc/init.d/nifi-registry
rm -rf /usr/local/fuusion
rm -f /tmp/.softnas-nifi-update.ini

# 16784 - Install/update nifidev files
if ! ps -aef | grep -q [i]nstall_update_nifi.sh; then
    /var/www/softnas/scripts/install_update_nifi.sh
fi

if [ `$SCRIPTDIR/which_host.sh` == "azure" ]; then
    echo "Checking waagent ...."
    WAVERSION=`curl -s https://github.com/Azure/WALinuxAgent/releases/latest | awk -F "tag/" '{print $2}' | awk -F '[v"]' '{print $2}'`
    echo "Latest waagent version available is $WAVERSION"
    INSWAVERSION=`waagent -version | grep WALinuxAgent | awk -F "[- ]" '{print $2}'`
    echo "Installed version is $INSWAVERSION"
    if [ "$WAVERSION" != "$INSWAVERSION" ]; then
        echo "Updating waagent ..."
        sudo apt-get install -y walinuxagent
        systemctl restart walinuxagent
    fi

    echo "Delete azure config folder"
    rm -rf /root/.azure

    #Fix for 6579
    export HOME=/root

    cloud-init clean --logs
    waagent -deprovision -force
    rm -f /etc/sudoers.d/90-cloud-init-users
    #18065 - Azure not able to change the password if the user already exists
    userdel buurst
    echo "ubuntu:*" | chpasswd
    history -c
    echo "SYSTEM PREPARED FOR CREATING AZURE IMAGE......"
fi

#Removed which_host.ini as docker won't work #18547
rm -f /var/www/softnas/config/which_host.ini


#!/bin/bash
# userinit.sh - one-time user initialization script for tasks that only get done once before user is active
#
SCRIPTDIR=/var/www/softnas/scripts

echo "Running Buurst user initialization..." >> /tmp/softnas.boot
# User initialization goes below this line

if ! grep buurst /etc/group ; then
	groupadd buurst
fi
if grep buurst /etc/passwd ; then
	usermod -g buurst -G buurst -m -d /home/buurst -s /bin/bash buurst
else	
	useradd -g buurst -G buurst -m -d /home/buurst -s /bin/bash buurst
fi

if [ ! -d "/home/buurst" ] ; then
	mkdir /home/buurst
	chown buurst:buurst /home/buurst
fi

if [ `$SCRIPTDIR/which_host.sh` == "vmware" ];
then
  echo "buurst:buurst" | sudo chpasswd
  echo "Buurst default passwords set for vmware" >> /tmp/softnas.boot
fi  

# Perform EC2 initialization
if [ `$SCRIPTDIR/which_host.sh` == "aws" ];
then
  # On Amazon EC2, we create higher-security default passwords to improve initial security (in case admin forgets or neglects to change them)
  # create secure default passwords for 'softnas' and 'root' user accounts - set to instance ID (private - only AWS admin knows this)
  INSTANCEID=`$SCRIPTDIR/getec2instanceid.sh`
  echo "buurst:$INSTANCEID" | sudo chpasswd
  echo "root:$INSTANCEID" | sudo chpasswd  # we don't enable SSH root login (you must sudo to it from ec2-user after SSH/PKI authentication only)
  rm -f /root/.ssh/authorized_keys # ensure there is no authorized_keys file for root (do not allow SSH to root, even with SSH keys)
  rm -f /home/cloud-user/.ssh/authorized_keys
  echo "Buurst default passwords set for EC2 users" >> /tmp/softnas.boot
fi

if [ `$SCRIPTDIR/which_host.sh` == "google" ];
then
	INSTANCEID=$(curl "http://metadata.google.internal/computeMetadata/v1/instance/id" -H "Metadata-Flavor: Google")
	echo "buurst:$INSTANCEID" | sudo chpasswd
	echo "Buurst default passwords set for GCloud users" >> /tmp/softnas.boot
fi

# ensure login processor has a unique, random password on this server
LOGINCFG="/var/www/softnas/config/login.ini"
fgrep "Pass4W0rd" $LOGINCFG >/dev/null 2>&1
if [ "$?" == "0" ]; then
	echo "Updating Login processor password..."
	LOGINPASSWD=`pwgen -n 16 -s | awk '{print $1}'`
	cp $LOGINCFG $LOGINCFG.BAK
	sed "s/Pass4W0rd/$LOGINPASSWD/" $LOGINCFG.BAK > $LOGINCFG
	echo "system:$LOGINPASSWD" | sudo chpasswd
	rm $LOGINCFG.BAK
	echo "Login processor password updated."
fi

# Ubuntu set password so that key based login is enabled
usermod -p "*" ubuntu

#18277 - Reset permissions so that user can login before the startup is completed
/var/www/softnas/scripts/reset_permissions.sh

echo "Buurst user initialization completed at `date`" >> /tmp/softnas.boot
touch /etc/softnas/userinit.completed


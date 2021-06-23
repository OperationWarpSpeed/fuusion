#!/bin/bash
set -ex

#########################################################################
#                                                                       #  
# UBUNTU 20.0 LTS (Focal) FUUSION INSTALLER 				#
#                                                                       #
# This script can be used for deploying in any cloud providers like AWS #
# AWS, Google, Azure , VMWare or other VPS.				#
# The script will dpeloy the latest dev version.			#
#########################################################################


#Check whether OS is bionic or not

REQUIRED_OS="focal"

if [ "$(lsb_release -c | awk '{print $2}')" = "$REQUIRED_OS" ]; then
    echo "Detected Ubuntu Focal .. Proceeding with installation .."
else
    echo "Ubuntu Focal NOT DETECTED. Installation aborted"
    exit 1
fi
alias APTINSTALL='sudo apt-get install -y'
INSTALLER_LOG="/tmp/softnas-installer.log"

function RUN_ALL_AND_LOG ()
{

export DEBIAN_FRONTEND=noninteractive
echo 'debconf debconf/frontend select Noninteractive' | debconf-set-selections

echolog()
(
echo "$1"
echo "$1" >> $INSTALLER_LOG
)

check_diskspace(){
  sudo apt-get install -y bc
  ROOTFREE=$(df -Ph / | awk '{ print $5; }'|tail -n1 |sed 's/%//')
  ROOTFREE=$(echo "100-$ROOTFREE"|bc)
  if [ "$ROOTFREE" -lt 25 ]; then
    echolog " ERROR - There is not enough disk free space on /. 25% is required, ${ROOTFREE}% available."
    exit 1
  fi
}

check_diskspace 

echolog "Update existing OS packages ... "
if 
    sudo apt update
    sudo apt upgrade -y -o Dpkg::Options::="--force-confnew"
then
    echolog "OS Package update Success ..."
else
    echolog "OS Package Update Failed ..."
    exit 2
fi   

echolog "Installing Required packages ..."

#dpkg-reconfigure debconf --frontend=noninteractive 
sudo apt-get install -y nginx libfuse2 libfuse-dev wget libffi-dev libssl1.1 libssl-dev build-essential zlib1g zlib1g-dev dracut-network ksh lsscsi
sudo apt-get install -y libattr1 libattr1-dev libuuid1 uuid-dev libblkid1 libblkid-dev libudev-dev libudev1 gcc
sudo apt-get install -y asciidoc pesign xmlto vim libpam0g-dev
sudo apt-get install -y libaudit-dev libaudit1 binutils-dev elfutils libelf1 libelf-dev
sudo apt-get install -y libncurses5-dev libnewt-dev libnuma-dev libpci-dev zlib1g-dev
sudo apt-get install -y mariadb-server
sudo apt-get install -y help2man snmpd snmp libsnmp-dev
sudo apt-get install -y unzip autoconf automake parted lsscsi
sudo apt-get install -y autogen libtool php-fpm php-cli php-common php-gd php-ldap php-mysql php-pdo php-dev php-curl php-bz2 php-gmp php-http
sudo apt-get install -y php-pear php-xml php-xmlrpc expect php-mbstring php-sqlite3 php-zip
sudo apt-get install -y postfix nano sipcalc
sudo apt-get install -y tmux net-tools  
sudo apt-get install -y proftpd  ntpdate ntp  shtool sshpass libjson-parse-perl mbuffer 
sudo apt-get install -y htop iotop iftop crudini linux-tools-common ioping acpid
sudo apt-get install -y pwgen plymouth alien jq mailutils uuid-runtime git
sudo apt-get install -y smbclient samba-common cron
sudo apt-get remove -y apache2

# Install mcrypt
sudo apt-get install -y libmcrypt4 libmcrypt-dev
if ! pecl list | grep mcrypt ; then
    printf "\n" | pecl install mcrypt
fi

# Install PHP IONCube Loader
PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION'.'"."'.'PHP_MINOR_VERSION;')
PHP_EXT_DIR="$(php -i | grep extension_dir | awk '{print $3}' | head -n1)"
echo 'extension="mcrypt.so"' > /etc/php/"$PHP_VERSION"/fpm/conf.d/mcrypt.ini
echo 'extension="mcrypt.so"' > /etc/php/"$PHP_VERSION"/cli/conf.d/mcrypt.ini

if [ "$PHP_VERSION" = "7.4" ]; then
    wget https://downloads.ioncube.com/loader_downloads/ioncube_loaders_lin_x86-64.tar.gz
    tar xzvf ioncube_loaders_lin_x86-64.tar.gz
    cp ioncube/ioncube_loader_lin_7.4.so "$PHP_EXT_DIR"
    rm -rf ioncube*
    echo 'zend_extension=ioncube_loader_lin_7.4.so' >> /etc/php/"$PHP_VERSION"/cli/php.ini
    echo 'zend_extension=ioncube_loader_lin_7.4.so' >> /etc/php/"$PHP_VERSION"/fpm/php.ini
else
	echo "Please install ioncube loader manually .. "
fi

sudo systemctl restart php"$PHP_VERSION"-fpm 

# PHP CONFIGURATION COMPLETED

# Monit Installation
sudo apt-get install -y monit

# Install NFS
sudo apt-get install -y nfs-kernel-server libnfs-utils
sudo systemctl enable --now nfs-server
rm -f /etc/exports && touch /etc/exports

# Net-snmp
sudo apt-get install -y snmpd snmp libsnmp-dev

# Throttle
# sudo apt-get install -y snapd
#sudo snap install throttle
wget https://www.softnas.com/software/throttle/throttle_1.2-2.2_amd64.deb
sudo apt-get install -y ./throttle_1.2-2.2_amd64.deb

# Python 
sudo apt-get install -y python3-pip python-is-python3 python3-dev 

# pip - Fix workaround
#WARNING: pip is being invoked by an old script wrapper. This will fail in a future version of pip.
#Please see https://github.com/pypa/pip/issues/5599 for advice on fixing the underlying issue.
#To avoid this problem you can invoke Python with '-m pip' instead of running pip directly.
echo 'python -m pip $@' > /usr/bin/pip
chmod 755 /usr/bin/pip

#Crudini requiers iniparse 
pip install iniparse

# Create Softnas User
if ! grep softnas /etc/passwd ; then
    groupadd softnas
    useradd -g softnas -G softnas -m -d /home/softnas -s /bin/bash softnas
    usermod -G root softnas
    useradd -m system
    sed -i '$ d' /etc/passwd
    echo "system:x:0:0::/home/system:/sbin/nologin" >> /etc/passwd
fi

if ! grep buurst /etc/passwd ; then
    groupadd buurst
    useradd -g buurst -G buurst -m -d /home/buurst -s /bin/bash buurst
    usermod -G root buurst
    echo "buurst:buurst" | chpasswd
fi


# Create a user name apache as its used by many scripts. Changing username requires, changes in many other files.
useradd -r apache -s /usr/sbin/nologin -d /var/www
#groupadd apache
mkdir /var/log/php-fpm/
chown apache: /var/log/php-fpm

# Generate cert
IPADDR=$(ifconfig | grep "inet " | head -n 1 | awk '{print $2}')
export IPADDR
openssl req -x509 -sha256 -nodes -newkey rsa:4096 -out /etc/pki/ca.crt -keyout /etc/pki/ca.key -subj "/C=US/ST=TX/L=Houston/O=SoftNAS/CN=${IPADDR}" -days 365

if [ ! -f /etc/ssl/certs/dhparam.pem ] ; then
    openssl dhparam -out /etc/ssl/certs/dhparam.pem 2048
fi

# Install 'attr' since setfattr tool will be used in preserving dos attribs
sudo apt-get install -y attr

# Cleanup packages
sudo apt-get autopurge -y 

if ! /var/www/softnas ; then
    cd /tmp
    VERSION=$(curl -0 https://mirror.softnas.com/fuusion/software/devupdate/version)
    URLBASE=https://mirror.softnas.com/fuusion/software/devupdate
    RPMPKG="softnas-"$VERSION".deb"
    wget $URLBASE/$RPMPKG
    cd /tmp
    apt install -y ./$RPMPKG
    /bin/bash /root/copytree.sh
    if [ -f /etc/init.d/softnas ]; then
      systemctl enable softnas
    fi
    WHOST=$(uname -r | awk -F "-" '{print $3}')
    if [ "$WHOST" == "generic" ]; then
	echo "vmware" > /var/www/softnas/config/which_host.ini
   else
	echo $WHOST > /var/www/softnas/config/which_host.ini
   fi
fi
chown root: /etc/sudoers

# Install Gcloud tools
if [ "$WHOST" == "gcp" ] ; then
	sudo apt-get install -y apt-transport-https ca-certificates gnupg
	echo "deb https://packages.cloud.google.com/apt cloud-sdk main" | sudo tee -a /etc/apt/sources.list.d/google-cloud-sdk.list
	curl https://packages.cloud.google.com/apt/doc/apt-key.gpg | sudo apt-key add -
	sudo apt-get update && sudo apt-get install -y google-cloud-sdk
	sudo apt-get install -y gce-compute-image-packages
fi

# Install AWS tools
pip install awscli==1.18.34
pip install AWSIoTPythonSDK==1.4.7

# Install Azure Cli - use eoan version as focal is not released as of April 2020
cd /tmp
wget https://aka.ms/InstallAzureCLIDeb
sed -i 's/CLI_REPO=$(lsb_release -cs)/CLI_REPO=eoan/g' InstallAzureCLIDeb
/bin/bash InstallAzureCLIDeb
rm -f InstallAzureCLIDeb

# Extjs old versions is still used by softnas and is not deployed in update scripts.
cd /var/www/html
wget https://www.softnas.com/software/extjs/extjs-5.1.tar.gz
wget https://www.softnas.com/software/extjs/extjs.tar.gz
tar xzvf extjs-5.1.tar.gz
tar xzvf extjs.tar.gz
chmod 755 extjs extjs_5.1

# Enable/Disable Services
sudo systemctl disable apparmor
sudo systemctl enable php"$PHP_VERSION"-fpm
sudo systemctl enable mariadb    
sudo systemctl enable proftpd
sudo systemctl enable ntp
sudo systemctl enable nginx
sudo systemctl restart nginx
sudo systemctl restart php"$PHP_VERSION"-fpm


# Initial Configuration
# ensure softnas.ini is reset to starting configuration
CONFIGDIR=/var/www/softnas/config
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

cp /var/www/softnas/config/monitoring.ini.prototype      /var/www/softnas/config/monitoring.ini
/var/www/softnas/scripts/reset_permissions.sh

cd /tmp
wget https://mirror.softnas.com/fuusion/software/devupdate/version
wget https://mirror.softnas.com/fuusion/software/devupdate/softnas_update_`cat version`.sh
chmod 755 softnas_update_`cat version`.sh
./softnas_update_`cat version`.sh devupdate

} # END OF FUNCTION RUN_ALL_AND_LOG


RUN_ALL_AND_LOG 2>&1 | tee -a $INSTALLER_LOG 

exit

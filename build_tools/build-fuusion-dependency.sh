#!/bin/bash

# For installing all packages required for Fuusion. Used for initial configuration of server.
# PHP, NGINX, OPENSSH


VERSION=1.0.0

mkdir fuusion-dependency
pushd fuusion-dependency
 mkdir DEBIAN
 cd DEBIAN
 cat <<EOF > control
Section: custom
Priority: optional
Maintainer: www.buurst.com
Version: $VERSION
Homepage: www.buurst.com
Package: fuusion-dependency
Architecture: amd64
Multi-Arch: foreign
Description: Installs all packages required for fuusion intial configuration
Pre-Depends: nginx, libfuse2, libfuse-dev, wget, libffi-dev, libssl1.1, libssl-dev, build-essential, zlib1g, zlib1g-dev,
	libattr1, libattr1-dev, libuuid1, uuid-dev, libblkid1, libblkid-dev, libudev-dev, libudev1, gcc,
	asciidoc, pesign, xmlto, vim, libpam0g-dev,
	libaudit-dev, libaudit1, binutils-dev, elfutils, libelf1, libelf-dev,
	libncurses5-dev, libnewt-dev, libnuma-dev, libpci-dev, zlib1g-dev,
	mariadb-server, help2man, snmpd, snmp, libsnmp-dev,
	unzip, autoconf, automake, autogen, libtool, 
	php-fpm, php-cli, php-common, php-gd, php-ldap, php-mysql, php-pdo, php-dev, php-curl, php-bz2, php-gmp, php-http,
	php-pear, php-xml, php-xmlrpc, expect, php-mbstring, php-sqlite3, php-zip,
	nano, sipcalc, tmux, net-tools, ntpdate, shtool, sshpass, libjson-parse-perl, mbuffer, 
	htop, iotop, iftop, crudini, linux-tools-common, ioping, acpid,
	pwgen, plymouth, alien, jq, mailutils, uuid-runtime, git,
	smbclient, samba-common, libmcrypt4, libmcrypt-dev,
	nfs-kernel-server, libnfs-utils, monit,
	snmpd, snmp, libsnmp-dev, attr, cron, throttle,
	python3-pip, python-is-python3, python3-dev, bc, logrotate
EOF

cat <<'EOF' > postinst
#!/bin/bash

echo "Package: *
Pin: origin mirror.softnas.com
Pin-Priority: 1001" > /etc/apt/preferences.d/fuusion

if ! pecl list | grep mcrypt ; then
    printf "\n" | pecl install mcrypt
fi

# Install PHP IONCube Loader
PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION'.'"."'.'PHP_MINOR_VERSION;')"
PHP_EXT_DIR="$(php -i | grep extension_dir | awk '{print $3}' | head -n1)"
echo 'extension="mcrypt.so"' > /etc/php/"$PHP_VERSION"/fpm/conf.d/mcrypt.ini
echo 'extension="mcrypt.so"' > /etc/php/"$PHP_VERSION"/cli/conf.d/mcrypt.ini

if [ "$PHP_VERSION" = "7.4" ]; then
    wget https://downloads.ioncube.com/loader_downloads/ioncube_loaders_lin_x86-64.tar.gz
    tar xzvf ioncube_loaders_lin_x86-64.tar.gz
    cp ioncube/ioncube_loader_lin_7.4.so "$PHP_EXT_DIR"
    rm -rf ioncube*
    if ! grep ioncube_loader /etc/php/"$PHP_VERSION"/fpm/php.ini ; then	
     echo 'zend_extension=ioncube_loader_lin_7.4.so' >> /etc/php/"$PHP_VERSION"/cli/php.ini
     echo 'zend_extension=ioncube_loader_lin_7.4.so' >> /etc/php/"$PHP_VERSION"/fpm/php.ini
    fi
else
	echo "Please install ioncube loader manually .. "
fi

#sudo systemctl restart php"$PHP_VERSION"-fpm

# PHP CONFIGURATION COMPLETED

rm -f /etc/exports && touch /etc/exports

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
chown root: /etc/sudoers

# Install Gcloud tools
# WHOST=$(uname -r | awk -F "-" '{print $3}')
#if [ "$WHOST" == "gcp" ] ; then
#	sudo apt-get install -y apt-transport-https ca-certificates gnupg
#	echo "deb https://packages.cloud.google.com/apt cloud-sdk main" | sudo tee -a /etc/apt/sources.list.d/google-cloud-sdk.list
#	curl https://packages.cloud.google.com/apt/doc/apt-key.gpg | sudo apt-key add -
#	sudo apt-get update && sudo apt-get install -y google-cloud-sdk
#	sudo apt-get install -y gce-compute-image-packages
#fi

# Install AWS tools
pip install awscli==1.18.34
pip install AWSIoTPythonSDK==1.4.7

if [ "$WHOST" == "azure" ] ; then
 # Install Azure Cli - use eoan version as focal is not released as of April 2020
 cd /tmp
 wget https://aka.ms/InstallAzureCLIDeb
 sed -i 's/CLI_REPO=$(lsb_release -cs)/CLI_REPO=eoan/g' InstallAzureCLIDeb
 /bin/bash InstallAzureCLIDeb
 rm -f InstallAzureCLIDeb
fi

# Enable/Disable Services
sudo systemctl disable apparmor
sudo systemctl enable php"$PHP_VERSION"-fpm
sudo systemctl enable mariadb
#sudo systemctl enable proftpd
sudo systemctl enable ntp
sudo systemctl enable nginx
/etc/init.d/nginx restart
/etc/init.d/php"$PHP_VERSION"-fpm start
/etc/init.d/mysql start

EOF

chmod 755 postinst
popd
dpkg-deb --build fuusion-dependency

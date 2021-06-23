#!/bin/bash
##
##  UPDATE YOUR CHANGES TO DEBIAN/postinst SCRIPT if you want to avail those changes via apt installer.
##  THIS SCRIPT IS NOT EXECUTED DURING THE INITIAL INSTALLATION OF FUUSION.
##  CHANGED BY Jimy Johny - 26 July 2020
##

set -x
# Copyright (c) Buurst Inc. - All Rights Reserved
BUILD_NUMBER="{{build_number}}"
CURRENT_SOFTNAS_VERSION=$(cat /var/www/softnas/version)
OLDEST_SUPPORTED_SOFTNAS_VERSION=2.0.0
PRODROOT="/var/www"
PRODPATH="/var/www/softnas"
SCRIPTDIR="/var/www/softnas/scripts"
CONFIGDIR="/var/www/softnas/config"
PRIORDIR="softnas.preupdate"
UPDATE_LOG="/tmp/softnas-update.log"
UPDATE_STATUS="/tmp/softnas-update.status"
UPDATE_STATE="/tmp/update_in_progress"
REBOOTREQUIRED="0"
UPGRADESOFTNAS="0"
NEWCERTIFICATE="1"
URLBASE="https://mirror.softnas.com/fuusion/aptrepo"
HOST_SERVICE_NAME=$($PRODPATH/scripts/which_host.sh)
FCP_PRODUCT_CODE=5hp0ljr9db9xa8wx9upinohf4
PATH="/usr/local/bin:$PATH"
export PATH
# use first IP address for SSL cert (excl localhost)
IPADDR=$(ifconfig | grep "inet " | head -n 1 | awk '{print $2}')
export IPADDR

PROGRESS_PATH="/tmp/progress.json"
PROGRESS_CHECKPOINTS=18
PROGRESS_CURRENT=0

HTTPD_LOCKFILE="/var/lock/subsys/httpd"

#### trapping signals ###############################################
trap "touch /tmp/softnas-update.SIGHUP; date; echo 'Ignoring SIGHUP'" SIGHUP
trap "touch /tmp/softnas-update.SIGTERM; date; echo 'Ignoring SIGTERM'" SIGTERM
trap "touch /tmp/softnas-update.EXIT; date; echo 'Accepting EXIT'; exit" EXIT
trap "touch /tmp/softnas-update.SIGINT; date; echo 'Accepting SIGINT'; exit" SIGINT

# #5609 - create /tmp/softnas-update.status to avoid monit error
touch $UPDATE_STATUS

# #3380 - prevent execution of update if it is already started:
grep 'true' $UPDATE_STATE 2>&1 > /dev/null && report_status 'Exiting, system is already running an update.' && exit 1


# This is the location where we are downloading all files before update begins
download_dir=/root/downloads
mkdir -p $download_dir


#### widely used functions ##########################################
#
# Only put helper functions here that are used by others and not install and
# configuration functions. Functions that manipulate progress bar, do cleanup,
# get system information for you, etc, are valid examples; Functions that
# perfomr any permanent system modification, are not.
#

safe_download(){
# Downloads a file from internet. If failed, reports an error and terminates
# update process.
#####
  time wget -N "$1"
  if [ "$?" != "0" ]; then
		report_status "Couldn't download \"$1\". Aborting update."
		update_failed
		exit 1
  fi
}

update_in_progress_on(){
  touch $UPDATE_STATE
  echo "true" > $UPDATE_STATE
}
update_in_progress_off(){
  touch $UPDATE_STATE
  echo "false" > $UPDATE_STATE
  echo "OK. " > $UPDATE_STATUS
  progress_bar_full
}
update_failed(){
  touch $UPDATE_STATE
  echo "false" > $UPDATE_STATE
  echo "ERROR " > $UPDATE_STATUS
  progress_bar_full
}
timestamp(){
  echo "$(date +%Y-%m-%d_%T)_$(date +%s%N);"
}

is_hvm(){
  [ "$(dmidecode -s system-manufacturer)" = "Xen" ] || [ "$(dmidecode -s system-manufacturer)" = "Amazon EC2" ]
}

progress_zero(){
  PROGRESS_CURRENT=$((PROGRESS_CURRENT + 1))
  echo "[{ \"total\": \"10\", \"current\": \"0\"}]" > \
    $PROGRESS_PATH
}
progress_bar_step(){
  # Progress bar step ahead
  ##########
  PROGRESS_MAX=$((PROGRESS_CHECKPOINTS - 1))
  if ! [ $PROGRESS_CURRENT -eq $PROGRESS_MAX ]; then
    # we should only progress when we're not at the maximum number of checkpoints
    PROGRESS_CURRENT=$((PROGRESS_CURRENT + 1))
  fi
  echo "[{ \"total\": \"$PROGRESS_CHECKPOINTS\", \"current\": \"$PROGRESS_CURRENT\"}]" > \
    $PROGRESS_PATH
}
progress_bar_step_back(){
  # Progress bar step back
  ##########
  PROGRESS_CURRENT=$((PROGRESS_CURRENT - 1))
  echo "[{ \"total\": \"$PROGRESS_CHECKPOINTS\", \"current\": \"$PROGRESS_CURRENT\"}]" > \
    $PROGRESS_PATH
}
progress_bar_full(){
  # Progress bar set to 100%
  ##########
  PROGRESS_CURRENT=$PROGRESS_CHECKPOINTS
  echo "[{ \"total\": \"$PROGRESS_CHECKPOINTS\", \"current\": \"$PROGRESS_CURRENT\"}]" > \
    $PROGRESS_PATH
}
progress_bar_step

enter_clean_dir(){
  mkdir -p "$1"
  rm -rf "$1"
  mkdir -p "$1"
  pushd "$1"
}
exit_clean_dir(){
  popd
  rm -rf "$1"
}

report_status(){
  # Echo to stdout and to file that is read by PHP.
  ##########
  echo "$1"
  curr_version_arr=(${CURRENT_SOFTNAS_VERSION//./ })
  if [[ "${curr_version_arr[0]}" -eq "4" && "${curr_version_arr[1]}" -lt "2" ]]; then
    echo '<div style="color:red;"><b>The upgrade process bar will remain at 89% for approximately 5-10 minutes then jump to 100%.</br>At this point please close the browser tab, wait 5 minutes and open a new tab to connect to StorageCenter.</br>For more detail see the release notes.</b></div>' > "$UPDATE_STATUS"
    echo "$1" >> "$UPDATE_STATUS" 2>&1
  else
    echo "$1" > "$UPDATE_STATUS" 2>&1
  fi
}

check_diskspace(){
  ROOTFREE=$(df -Ph / | awk '{ print $5; }'|tail -n1 |sed 's/%//')
  ROOTFREE=$(echo "100-$ROOTFREE"|bc)
  if [ $ROOTFREE -lt 25 ]; then
    report_status " ERROR - There is not enough disk free space on /. 25% is required, ${ROOTFREE}% available."
    sleep 10
    update_failed
    exit 1
  fi
}

check_is_china(){

if [ "$HOST_SERVICE_NAME" = "aws" ]; then
REGION=`curl -s http://169.254.169.254/latest/dynamic/instance-identity/document | awk -F\" '/region/ {print $4}'`
if [ "$REGION" == "cn-north-1" ] || [ "$REGION" == "cn-northwest-1" ] ; then
    report_status "Region is $REGION. Updating hosts file to china mirror"
    MIRROR_IP="$(host chinamirror.softnas.com | awk '{print $4}')"
    if ! grep softnas.com /etc/hosts ; then
        echo "$MIRROR_IP mirror.softnas.com softnas.com www.softnas.com" >> /etc/hosts
    fi
fi
fi
}

check_internet(){
  # Check internet connection and exit with error if absent
  ##########
  time wget -q --tries=5 --timeout=5 https://mirror.softnas.com > /dev/null 2>&1
  if [ $? -ne 0 ]; then
    report_status " ERROR - There is no internet connection to mirror.softnas.com... \
                    Did you forget to define NAT rules?"
    sleep 10
    update_failed
    exit 1
  fi
  time wget -q --tries=5 --timeout=5 https://www.buurst.com > /dev/null 2>&1
  if [ $? -ne 0 ]; then
    report_status " ERROR - There is no internet connection to www.buurst.com... \
                    Did you forget to define NAT rules?"
    sleep 10
    update_failed
    exit 1
  fi
}

get_total_ram(){
  # Print total system ram in bytes
  ##########
  free -bt | awk 'END {print $2}'
}

check_if_supported_version(){
# Checks if current version of softnas supports this update. If not, terminates
# update and shows migration options link.
#####
  version_is_supported=false
  for i in {1..3}; do
    current_version_number=$( echo "$CURRENT_SOFTNAS_VERSION" | awk -F'.' "{print \$$i}" )
    oldest_version_number=$( echo "$OLDEST_SUPPORTED_SOFTNAS_VERSION" | awk -F'.' "{print \$$i}" )
    if [ "$current_version_number" -gt "$oldest_version_number" ]; then
      version_is_supported=true
      break
    fi
    if [ "$current_version_number" -lt "$oldest_version_number" ]; then
      version_is_supported=false
      break
    fi
  done
  if [ "$current_version_number" = "$oldest_version_number" ]; then
    version_is_supported=true
  fi
  if ! $version_is_supported; then
    sleep 5
    progress_zero
    touch "$UPDATE_STATE"
    echo "false" > "$UPDATE_STATE"
    
    migration_info_window="<div style='position:fixed;height:115px;width:500px;top:120px;left:330px;border-style:solid;border-width:1px;border-color:#a2b1c5;padding:10px;background-color:#ced9e7;border-radius:5px;box-shadow:0px 2px 5px #888888;z-index:999;'>
    <span style='color:#04468c;font-weight:bold;'>Update not supported</span><br/><br/>
    Your version does not support 1-click upgrades. Click button below to see info on upgrading your version.<br/><br/>
    <div style='width:100%;text-align:center;'><input type='button' value='How to Migrate Buurst' style='border-radius:5px; background: linear-gradient(#fff 50%, #ddd 25%,#eee); color:#222; border-color:#ccc; border-width:1px; border-style:solid; padding:5px;' onclick='window.open(\"https://www.softnas.com/helpdesk/index.php?/Knowledgebase/Article/View/7/0/softnas-kb-how-to-migrate-softnas\")' /> </div>
    </div>"
    echo "$migration_info_window" > "$UPDATE_STATUS" 2>&1
    exit 1
  fi
}

#
#
#### ^^^ widely used functions ^^^ ##################################



#### install and configuration functions ############################
#
# This is a place for functions that do actual installation or configuration on
# the system. If any non-yum package is needed, it needs to be downloaded prior
# to taking any system modification action, so downloading needs to be in
# separate function.

cert_generate() {
  openssl req -x509 -sha256 -nodes -newkey rsa:2048 -out /etc/pki/ca.crt -keyout /etc/pki/ca.key -subj "/C=US/ST=TX/L=Houston/O=Buurst/CN=${IPADDR}" -days 365
}

check_azure_cli2(){
  HOST_SERVICE_NAME=$($PRODPATH/scripts/which_host.sh)
  if [ "$HOST_SERVICE_NAME" = "azure" ]; then
    azure_cli2_version="2.5.0"
    install_azure_cli2=false
    current_azure_cli2_version=$(az --version | grep azure-cli |  awk '{print $2}' | tr -d '()')
    if [ "$current_azure_cli2_version" != "$azure_cli2_version" ]; then
      install_azure_cli2=true
    fi
  fi
}

install_azure_cli2(){
  if $install_azure_cli2; then
    report_status "Installing azure cli $azure_cli2_version ..."
    sudo apt-get install -y azure-cli
  fi
}

clean_old_update_files(){
  # Clean files left from previous update
  ##########

  # start with clean update status
  rm -f $UPDATE_STATUS 2> /dev/null 1> /dev/null

  # remove any prior update status
  report_status "Removing prior updates..."
  rm -rf /root/installtree 2> /dev/null 1> /dev/null
  # remove old RPM files (if any) before the update is received
  rm -f /root/zfsrpms/*.rpm 2> /dev/null 1> /dev/null
  rm -rf /root/downloads/zfs/* 2> /dev/null 1> /dev/null
}

softnas_update(){
  # Download and install softnas packages
  ##########
  
  report_status "Update starting..."
  selected_version=$(cat /tmp/version)
  echo "Buurst(tm) Software Update, version ${selected_version} at $(date)"
  echo "Updating . . ."

  enter_clean_dir softnas
    if [ "$UPDATETYPE" = "customupdate" ]; then
      echo "$CUSTOM_UPDATE_VERSION" > version
      echo "$CUSTOM_UPDATE_VERSION" > /tmp/version
      RPMPKG="fuusion-$CUSTOM_UPDATE_VERSION.deb"
    else
      safe_download $URLBASE/version
      latest_prod_version=$(cat version)
      if [ "${latest_prod_version}" != "${selected_version}" ]; then
        report_status "Warning! you are updating to the version (${selected_version}) which is not the latest (${latest_prod_version})"
      fi
      RPMPKG="fuusion-${selected_version}.deb"
    fi
  
    report_status "Installing Fuusion updates..."

    rm -rf "$PRODROOT/$PRIORDIR"
    echo "Making backup copy of existing installation"
    cp -R "$PRODPATH" "$PRODROOT/$PRIORDIR"
    time sudo apt -y remove softnas
    if [ "$UPDATETYPE" = "customupdate" ]; then
	    sudo apt-get install -y fuusion=$CUSTOM_UPDATE_VERSION fuusion-ui=$CUSTOM_UPDATE_VERSION fuusion-ultrafast fuusion-nifi
    else	    
	    sudo apt-get install -y fuusion fuusion-ui fuusion-ultrafast fuusion-nifi
    fi
    if [ "$?" != "0" ]; then
      report_status " ERROR - DEB failed to install the update. See update \
                      log $UPDATE_LOG for details."
      rm -rf "$PRODPATH"
      cp -R "$PRODROOT/$PRIORDIR" "$PRODPATH"
      exit 1
    fi
    # 18937 -  preserve and restore ultrafast config file
    [ -f /opt/ultra.conf.bk ] && mv -f /opt/ultra.conf.bk /opt/ultrafast/conf/ultra.conf 
    report_status "Buurst updates installed successfully."
  exit_clean_dir softnas
}

check_install_packages_and_updates(){
  # Install any new packages or package updates.
  ##########
  
  report_status "Installing packages..."
  
  enter_clean_dir packages

    # Azure Specific updates
    if [ "$HOST_SERVICE_NAME" = "azure" ]; then
      time sudo apt install -y install walinuxagent
      touch /etc/udev/rules.d/60-raw.rules
    fi

    # awscli
    AWSCLI_INSTALLED=$(pip list | grep awscli | grep 1.18.34)
    if [ "$AWSCLI_INSTALLED" = "" ]; then
        yes | pip uninstall awscli
        rm -rf /tmp/pip-build-root
        pip install awscli==1.18.34
    fi
    ### UPDATE PACKAGES:

  exit_clean_dir packages
}

install_fuusion_repo(){

  if [ "${TESTUPDATE}" = "true" ]; then
    report_status "TEST Update running..."
    URLBASE=https://mirror.softnas.com/fuusion/aptrepo_test
  fi
  if [ "${DEVUPDATE}" = "true" ]; then
    report_status "DEV Update running..."
    URLBASE=https://mirror.softnas.com/fuusion/aptrepo_dev
  fi
  if [ "${CUSTOMUPDATE}" = "true" ]; then
    report_status "Custom Update running..."
    URLBASE=https://mirror.softnas.com/fuusion/aptrepo_custom
  fi
	report_status "Configuring fuusion repository..."
	curl -0 --http1.1 $URLBASE/keyFile | apt-key add
	echo "deb $URLBASE /" > /etc/apt/sources.list.d/fuusion.list
	echo 'Package: *
Pin: origin mirror.softnas.com
Pin-Priority: 1001' > /etc/apt/preferences.d/fuusion
	sudo apt update
}	

restart_fcp_if_aws(){
  if [ "$HOST_SERVICE_NAME" = "aws" ]; then
    result=$(wget -qO- http://169.254.169.254/latest/meta-data/product-codes/ | grep -c "$FCP_PRODUCT_CODE")
    if [[ "$result" == "1" ]]; then
      echo "Restarting FCP . . ."
      result=$(ps aux | grep -c "[s]oftnas-console-app.jar")
      if [[ "$result" != "0" ]]; then
        kill -9 "$(ps aux | grep  "[s]oftnas-console-app.jar" | awk '{ print $2 }')"
        sleep 2
        result=$(ps aux | grep -c "[s]oftnas-console-app.jar")
        if [[ "$result" == "0" ]]; then
          nohup java -jar /var/www/softnas/scripts/softnas-console-app.jar start > /dev/null 2>&1 &
        fi
      else
        java -jar /var/www/softnas/scripts/softnas-console-app.jar > /dev/null 2>&1
      fi
    fi
  fi
}

fix_which_host(){
# 4687 
HOST_INI="$PRODPATH/config/which_host.ini"
if [ ! -f "$HOST_INI" ]; then
    $PRODPATH/scripts/which_host.sh > $HOST_INI
fi    
}

# 18314 - Ubuntu tunings for NiFi/Fuusion
set_nifi_tunings(){
  # $1 - Previous value of fs.file-max before replacing /etc/sysctl.conf from the update

  # Settings below are already in /etc/sysctl.conf so we will reload them.
  #   sudo sysctl -w vm.swappiness=0
  #   sudo sysctl -w net.ipv4.ip_local_port_range="10000 65000"
  #   sudo sysctl -w net.netfilter.nf_conntrack_tcp_timeout_time_wait=1
  sudo modprobe nf_conntrack
  # 16154 - If user has configured MAX_FD > 1048756, preserve it, otherwise, set to default 1048756
  MAX_FD="$1"
  if [ ! -z "$MAX_FD" ] && [ $MAX_FD -lt 1048756 ]; then
    MAX_FD=1048756
  fi
  sed -i "s/fs.file-max.*/fs.file-max=$MAX_FD/g" /etc/sysctl.conf
  sudo sysctl -p /etc/sysctl.conf

  # Settings below persisted also in /etc/limits.conf so only load for current session
  #   *  soft  nofile   65535
  #   *  hard  nofile   65535
  #   *  hard  nproc    10000
  #   *  soft  nproc    10000
  ulimit -SHn 65535
  ulimit -SHu 10000
}


## End of Upgrade

#
# ps aux | grep java | grep -v grep | awk '{ print $2 }'
#
###########
#
# SCRIPT STARTS HERE
#
##########

check_is_china

check_if_supported_version

update_in_progress_on

# cd to main directory
cd /root
# stop monit so it doesn't restart services during update
/etc/init.d/monit stop

UPDATETYPE=$1
DEVUPDATE=false
TESTUPDATE=false
CUSTOMUPDATE=false
CUSTOM_UPDATE_VERSION=
if [ "$1" = "devupdate" ]; then
  DEVUPDATE=true
  echo          "###############################"
  report_status "# devupdate in progress        "
  echo          "###############################"
  sleep 5
elif [ "$1" = "testupdate" ]; then
  TESTUPDATE=true
  if [ "$BUILD_NUMBER" = "" ]; then
    BUILD_NUMBER_REPORT=
  else
    BUILD_NUMBER_REPORT="for test build: $BUILD_NUMBER"
  fi
  echo          "###############################"
  report_status "# testupdate in progress $BUILD_NUMBER_REPORT"
  echo          "###############################"
  sleep 5
elif [ "$1" = "customupdate" ]; then
  CUSTOMUPDATE=true
  if [ "$2" != "" ]; then
    CUSTOM_UPDATE_VERSION="$2"
    echo          "###############################"
    report_status "# custom update in progress for custom build: $CUSTOM_UPDATE_VERSION"
    echo          "###############################"
    sleep 5
  else
    echo "Custom update version not specified. Exiting update!"
    sleep 5
    update_failed
    exit 1
  fi
elif [ "$1" = "devnextupdate" ]; then
  DEVNEXTUPDATE=true
  echo          "###############################"
  report_status "# devnextupdate in progress        "
  echo          "###############################"
  sleep 5
elif [ "$1" = "stableupdate" ]; then
  STABLEUPDATE=true
  echo          "###############################"
  report_status "# stableupdate in progress        "
  echo          "###############################"
  sleep 5
fi

# stop nifi and ultrafast first to avoid problems with existing data flows
# nifi is using ultrafast so it should be stopped prior to ultrafast
if [ -f /etc/init.d/nifi ]; then
  report_status "Stopping nifi service..."
  /etc/init.d/nifi stop
fi

report_status "Stopping ultrafast service..."
if [ -f /opt/ultrafast/bin/ultrafast ]; then
  /opt/ultrafast/bin/ultrafast stop
  # 18937 - preserve and restore ultrafast config file
  [ -f  /opt/ultrafast/conf/ultra.conf ] && cp -f /opt/ultrafast/conf/ultra.conf /opt/ultra.conf.bk
fi

### perform checks
#
# generate postfix transport mapping
sudo postmap /etc/postfix/transport
# restart postfix
service postfix restart
# clear queue
sudo postqueue -f

report_status "Performing system checks..."
fix_which_host
check_diskspace
check_internet
check_azure_cli2

### perform downloads
#
report_status "Downloading packages..."
clean_old_update_files
  progress_bar_step

### perform upgrades
#
install_fuusion_repo
check_install_packages_and_updates
  progress_bar_step
softnas_update
  progress_bar_step
install_azure_cli2
  progress_bar_step
#restart_fcp_if_aws
#  progress_bar_step
  
report_status "Updates installed. Setting permissions..."

# create symlink into /var/log/softnas
ln -s /var/www/softnas/logs /var/log/softnas 2> /dev/null

# set configuration file permissions just in case
/var/www/softnas/scripts/reset_permissions.sh

################################
rm -rf /tmp/softnascfg/*

# 16154 -  Get user configured fd limit before overwriting /etc/sysctl.conf via /root/copytree.sh
MAX_FD=$(cat /proc/sys/fs/file-max)

# installtree copies pre-configured system configuration files,
# script and other system mods
/root/copytree.sh

# 18314 - Ubuntu tunings for NiFi
set_nifi_tunings $MAX_FD

# install SoftNAS Console on VM (non-EC2) systems only
if [ "$HOST_SERVICE_NAME" = "vmware" ]; then
  # force console re-install
  rm -f /etc/init/softnas_config.conf 
  report_status " Updating Fuusion Console (updates available after next \
                  reboot - no reboot required now)..."
  CONSOLE_DIR="/usr/local/softnas/console"
  pushd $CONSOLE_DIR
    $CONSOLE_DIR/install.sh
    case "$?" in
      0) 
        report_status " Fuusion Console installation/update completed. \
                        Console will be activated upon next reboot."
        ;;
      1)  
        report_status "Fuusion Console installation must be installed as root."
        ;;
      2)  
        report_status "Fuusion Console is already installed."
        ;;
      *) echo "Fuusion Console - unknown exit status"
       ;;
    esac
  popd
fi

report_status "Restarting services..."

# Ticket 4919 - add localhost in hosts file
if ! [ -f /etc/hosts.d/localhost ]; then
  echo "Fix DNS hostnames"
  echo "127.0.0.1 localhost" > /etc/hosts.d/localhost
fi
bash ${SCRIPTDIR}/hostfilegenerator.sh
check_is_china

# add hostname in hosts file (required by monit)
if [ "$HOST_SERVICE_NAME" = "aws" ]; then
  sed "/$(hostname)/d" /etc/hosts.d/system
  echo "$(ip route get 255.255.255.255 | grep -Po '(?<=src )(\d{1,3}.){4}')" "$(hostname)" >> /etc/hosts.d/system
fi

if [ "$DEVUPDATE" = "true" ]; then
  # extend session timeout for devupdate
  report_status "Extend session timeout to 99 minutes for devupdate"
  time sudo apt-get install -y crudini 1> /dev/null 2> /dev/null
  CURRENT_TIMEOUT=$(crudini --get /var/www/softnas/config/login.ini login timeout)
  if [[ ${CURRENT_TIMEOUT} == '"15"' ]]; then
    crudini --set /var/www/softnas/config/login.ini login timeout \"99\"
  fi
fi

### Remove installation leftovers from this and older updates
( cd /root/; rm -rf SoftNAS_SP2* zfs* sernet-samba*.tar.gz repos index.html* copytree.* installtree )

# Disable startup of nfs daemon
systemctl disable nfs-kernel-server

# Rotate secure log file daily. Removed for custom configuration
if [ -f /etc/logrotate.d/syslog ] ; then
    sed -i '/secure/d' /etc/logrotate.d/syslog
fi

# regenerate SSL certs if needed (2015-07-24 kashpande)
if [ -e /etc/pki/ca.crt ] && [ $NEWCERTIFICATE -eq 1 ]; then
  echo "New SSL certificate regeneration begins. Verify certificate."
  VERIFY=$(openssl verify /etc/pki/ca.crt)
  if [[ $VERIFY == *"error 10 at 0 depth lookup:certificate has expired"* ]]
  then
    # certificate was invalid, rm and regenerate
    rm -f /etc/pki/ca.crt /etc/pki/ca.key
    cert_generate
  else
    echo "SSL certificate is not expired - ignoring regeneration"
  fi
else
  echo "No SSL certificate was found. Generating now.."
  cert_generate
fi
# regenerate SSL cert with SHA256
certAlgorithm=$(openssl x509 -in /etc/pki/ca.crt -text -noout | grep "Signature Algorithm:" | awk '{print $3}' | sed -n '1p')
if [[ "${certAlgorithm}" != "sha256WithRSAEncryption" ]]; then
    rm -f /etc/pki/ca.crt /etc/pki/ca.key
  cert_generate
fi

# 15264 - Enable DH support
if [ ! -f /etc/ssl/certs/dhparam.pem ] ; then
    openssl dhparam -out /etc/ssl/certs/dhparam.pem 2048
fi

# Import AWS CA certs for java
bash $PRODPATH/scripts/import_aws_ca.sh

#17575 - Add buurst as default user
if ! grep buurst /etc/passwd ; then
    useradd buurst
fi

#one last yum clean to make sure that we do not cache meta data
if [ -f "/tmp/softnas-optional.sh" ]; then
  chmod +x /tmp/softnas-optional.sh
 sh /tmp/softnas-optional.sh
rm -rf /tmp/softnas-optional.sh
fi

# 18317 - Fix python error of softnas-cmd
sed -ie 's/print json.dumps(sys.stdin.read())/print(json.dumps(sys.stdin.read()))/g' /var/www/softnas/api/softnas-cmd
# 18957 - Remove quotes of PATH value in /etc/environment
sed -ie 's/\"//g' /etc/environment

# finish up and handle errors if failed
#TODO: moving non-existing dir to new location??
if [ ! -d $PRODPATH ]; then
  report_status " ERROR: Update of UI was unsuccessful. Buurst update was \
                  incomplete. $UPDATE_LOG"
  mv $PRODPATH $PRODROOT/softnas.failed
  mv $PRODROOT/$PRIORDIR $PRODPATH
else
  # record this most recent update type that succeeded in config folder for
  # later reference (so we know where this update originated)
  echo "$UPDATETYPE" > "$PRODPATH/config/softnas-updatetype"
  progress_bar_full

  if [ $REBOOTREQUIRED -eq 1 ]; then
    report_status " REBOOTING Fuusion... Please wait up to 15 minutes for \
                    Fuusion restart to complete updates to version \
                    $(cat /tmp/version) ..."
    touch /tmp/softnas-update.reboot
    touch /tmp/softnas-update.reboot_cloudessentials
    sleep 10
  else
    report_status " Update to version $(cat /tmp/version) completed at \
                    $(date) "
  fi
fi

# 15127 - update version file to be sure we're correct
cp /tmp/version /var/www/softnas/version

# 16708 - clean up /root/downloads
rm -rf $download_dir/*

progress_bar_full
update_in_progress_off

$PRODPATH/scripts/config-generator-monit.sh

#5613 - save previous log from every update
cp "$UPDATE_LOG" /tmp/softnas-update-$(date +%s).log

if [ "$REBOOTREQUIRED" = "1" ]; then
  sync; sync
  reboot
else
  # 18172 - Install/update nifi and nifi registry if no reboot
  bash $PRODPATH/scripts/install_update_nifi.sh
  NIFI_HOME=$(/var/www/softnas/scripts/nifi_tls_utils.sh --getNifiHome)
  # 18882 - Use custom nifi.sh with LD_PRELOAD option
  mv -f $NIFI_HOME/bin/nifi.sh $NIFI_HOME/bin/nifi.sh.original
  cp -f $PRODPATH/scripts/nifi.sh $NIFI_HOME/bin/nifi.sh
  # 18313 - Set NiFi tunings 
  bash $PRODPATH/scripts/nifi_custom_props.sh $NIFI_HOME
  bash $PRODPATH/scripts/nifi-service.sh restart
  bash $PRODPATH/scripts/nifi_tls_utils.sh --waitNifi
fi

exit 0

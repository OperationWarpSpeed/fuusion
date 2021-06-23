#!/bin/bash

# Script for building Azure Softnas Image. 

# STEPS :
    # Builds zfs root ubuntu 20 OS
    # Install softnas base packages
    # Runs softnas update script
    # Create an Azure Image
    # Launch an instance from the image

# Requirements for running this script
    # Must be run this script from Ubuntu 18 or 19
    # Azure service principal username , password and tenant id
    # Must be executed as root user

################################################################

# Read Arguments
TEMP=`getopt -o h,v:,u:,p:,t: --long help,version:,username:,password:,tenant: -n 'build_azure_ubuntu.sh' -- "$@"`
eval set -- "$TEMP"

# extract options and their arguments into variables.
while true ; do
    case "$1" in
        -h|--help) echo "
NAME
    ./build_azure_ubuntu.sh - Build Azure Softnas Image
SYNOPSIS
    ./build_azure_ubuntu.sh [-u USERNAME ] [-p PASSWORD ] [-t TENANT ID ] [-v CustomUpdateVersion]

Requirements for running this script
    # Must be run this script from Ubuntu 18 or 19
    # Azure service principal username , password and tenant id
    # Must be executed as root user
    # Custom Update version

======================================== END ========================================= "

		   HELP="TRUE"
           exit 0
		   shift ;;
        -v|--version)
            case "$2" in
                "") shift 2 ;;
                *) CUSTOM_UPDATE_VERSION=$2 ; 
		    shift 2 ;;
            esac ;;
        -u|--username)
            case "$2" in
                "") shift 2 ;;
                *) USERNAME=$2 ;
		    shift 2 ;;
            esac ;;
        -p|--password)
            case "$2" in
                "") shift 2 ;;
                *) PASSWORD=$2 ;
		    shift 2 ;;
            esac ;;
        -t|--tenant)
            case "$2" in
                "") shift 2 ;;
                *) TENANT=$2 ;
		    shift 2 ;;
            esac ;;
        --) shift ; break ;;
        *) echo "Incorrect Syntax!" ; 
        exit 1 ;;
    esac
done

if [ -z "$CUSTOM_UPDATE_VERSION" ] ; then
    echo "Custom update version not found."
    ./build_azure_ubuntu.sh --help
    exit 1
fi

if [ -z "$USERNAME" ] ; then
    echo "Azure Username not found."
    ./build_azure_ubuntu.sh --help
    exit 1
fi

if [ -z "$PASSWORD" ] ; then
    echo "Azure password not found."
    ./build_azure_ubuntu.sh --help
    exit 1
fi

if [ -z "$TENANT" ] ; then
    echo "Azure tenant id not found."
    ./build_azure_ubuntu.sh --help
    exit 1
fi

function RUN_ALL_AND_LOG ()
{

URLBASE=https://www.softnas.com/software/softnas/customupdate
if ! wget $URLBASE/softnas_update_$CUSTOM_UPDATE_VERSION.sh ; then
 echo "Custom Update Version script not found - $URLBASE/softnas_update_$CUSTOM_UPDATE_VERSION.sh - FAILED"
 exit 1
fi

if ! wget https://www.softnas.com/software/softnas/ubuntu/ubuntu_installer_azure.sh ; then
    echo "Ubuntu Softnas Installer script not found - https://www.softnas.com/software/softnas/ubuntu/ubuntu_installer_azure.sh "
    exit 1
fi

#Install azure-cli tools and verify azure login
curl -sL https://aka.ms/InstallAzureCLIDeb | sudo bash
set -e
az login --service-principal -u $USERNAME -p $PASSWORD --tenant $TENANT 

##################################################################################################################

## BUILD UBUNTU 20 BOOT STRAPPER SCRIPT

echo '
#!/bin/bash

set -e

# Update APT with new sources
apt-get update

# Do not configure grub during package install
echo "grub-pc grub-pc/install_devices_empty select true" | debconf-set-selections
echo "grub-pc grub-pc/install_devices select" | debconf-set-selections

# Install various packages needed for a booting system
DEBIAN_FRONTEND=noninteractive apt-get install -y linux-image-azure linux-image-azure grub-pc zfs-zed zfsutils-linux zfs-initramfs gdisk curl wget

# Set the locale to en_US.UTF-8
locale-gen --purge en_US.UTF-8
echo LANG="en_US.UTF-8" > /etc/default/locale
echo LANGUAGE="en_US:en" >> /etc/default/locale

# Install OpenSSH
apt-get install -y --no-install-recommends openssh-server

# Install GRUB
# shellcheck disable=SC2016
grub-probe /
grub-install /dev/sdc

# Configure and update GRUB
update-grub

# Install standard packages
DEBIAN_FRONTEND=noninteractive apt-get install -y ubuntu-standard cloud-init vim
groupadd ubuntu
useradd -g ubuntu -G ubuntu -m -d /home/ubuntu -s /bin/bash ubuntu

#Azure Specific package
apt-get install -y walinuxagent
#waagent -force -deprovision

#Disable cloud-init to avoid mounting sda as sdb
systemctl disable cloud-init
' > chroot-bootstrap.sh

chmod 755 chroot-bootstrap.sh

## END OF UBUNTU 20 BOOT STRAPPER SCRIPT
##

# Update apt and install required packages
DEBIAN_FRONTEND=noninteractive sudo apt-get install -y \
	zfs-zed zfsutils-linux zfs-initramfs zfs-dkms debootstrap gdisk perl jq

# Partition the new root EBS volume
sudo sgdisk -Zg -n1:0:4095 -t1:EF02 -c1:GRUB -n2:0:0 -t2:BF01 -c2:ZFS /dev/sdc

# Create zpool and filesystems on the new EBS volume
umount /mnt
sudo zpool create -f \
	-o altroot=/mnt \
	-o ashift=12 \
	-o cachefile=/etc/zfs/zpool.cache \
	-O canmount=off \
	-O compression=lz4 \
	-O atime=off \
	-O normalization=formD \
	-m none \
	rpool \
	/dev/sdc2

# Root file system
sudo zfs create \
	-o canmount=noauto \
	-o mountpoint=/ \
	rpool/ROOT

sudo zfs mount rpool/ROOT


# Display ZFS output for debugging purposes
sudo zpool status
sudo zfs list

# Bootstrap Ubuntu Yakkety into /mnt
sudo debootstrap --arch amd64 focal /mnt
#sudo cp /tmp/sources.list /mnt/etc/apt/sources.list

# Copy the zpool cache
sudo mkdir -p /mnt/etc/zfs
sudo cp -p /etc/zfs/zpool.cache /mnt/etc/zfs/zpool.cache

# Create mount points and mount the filesystem
sudo mkdir -p /mnt/{dev,proc,sys}
sudo mount --rbind /dev /mnt/dev
sudo mount --rbind /proc /mnt/proc
sudo mount --rbind /sys /mnt/sys

#Copy grub settings
mkdir /mnt/etc/default/grub.d/
cp /etc/default/grub.d/50-cloudimg-settings.cfg /mnt/etc/default/grub.d/

# Copy the bootstrap script into place and execute inside chroot
sudo cp chroot-bootstrap.sh /mnt/tmp/chroot-bootstrap.sh

# This script is described in the following section
sudo chroot /mnt /tmp/chroot-bootstrap.sh
sudo rm -f /mnt/tmp/chroot-bootstrap.sh

#Configure ssh root login temporarily. Required to login and install softnas packages
echo | ssh-keygen -f /root/sshkey -q -N ""
mkdir /mnt/root/.ssh
cat /root/sshkey.pub > /mnt/root/.ssh/authorized_keys
chown -R root:root /mnt/root/.ssh/
chmod 600 /mnt/root/.ssh/authorized_keys

#Install softnas specific initial packages
chmod 755 ubuntu_installer_azure.sh
mv ubuntu_installer_azure.sh /mnt/root/

#Install Softnas Specific Updates
# We dont want to reboot the update script automatically
head -n -6 softnas_update_$CUSTOM_UPDATE_VERSION.sh > tmp.sh
mv tmp.sh softnas_update_$CUSTOM_UPDATE_VERSION.sh
echo "exit 0 " >> softnas_update_$CUSTOM_UPDATE_VERSION.sh

chmod 755 softnas_update_$CUSTOM_UPDATE_VERSION.sh
mv softnas_update_$CUSTOM_UPDATE_VERSION.sh /mnt/root/
echo $CUSTOM_UPDATE_VERSION > /mnt/root/version

## CREATE DPPROVISION SCRIPT
echo '
#!/bin/bash
set -x
/var/www/softnas/scripts/sysprep.sh 
cloud-init clean --logs
waagent -deprovision -force 
#Waagent is not able to set password when the image is launched if the user already exists"
userdel softnas
rm -f /etc/sudoers.d/90-cloud-init-users
rm -f /root/* &
exit 0
' > /mnt/root/deprovision.sh
chmod 755 /mnt/root/deprovision.sh
## END OF DEPROVISION SCRIPT

# Remove temporary sources list - CloudInit regenerates it
sudo rm -f /mnt/etc/apt/sources.list

#Copy netplan yaml file for network settings
cp /etc/netplan/50-cloud-init.yaml /mnt/etc/netplan/50-cloud-init.yaml

# This could perhaps be replaced (more reliably) with an `lsof | grep -v /mnt` loop,
# however in approximately 20 runs, the bind mounts have not failed to unmount.
sleep 10 

# Unmount bind mounts
sudo umount -l /mnt/dev
sudo umount -l /mnt/proc
sudo umount -l /mnt/sys

# Export the zpool
sudo zpool export rpool

#Build Azure Image
curl -H Metadata:true "http://169.254.169.254/metadata/instance?api-version=2019-06-01" | json_pp > /tmp/instance 
DISKNAME=$(cat /tmp/instance |  jq -r .compute.storageProfile.dataDisks[0].name)
RGROUP=$(cat /tmp/instance | jq -r .compute.resourceGroupName)
VMNAME=$(cat /tmp/instance | jq -r .compute.name)
LOCATION=$(cat /tmp/instance | jq -r .compute.location)
ADMINPASS=$(head /dev/urandom | tr -dc A-Za-z0-9 | head -c 12 ; echo ''@)
SOFTNAS_VMNAME=$(head /dev/urandom | tr -dc 0-9 | head -c 5 ; echo '')
IMAGENAME=softnas-$(head /dev/urandom | tr -dc A-Za-z0-9 | head -c 13 ; echo '')

az vm disk detach -g $RGROUP --vm-name $VMNAME --name $DISKNAME 
az vm create -g $RGROUP -n temp-$SOFTNAS_VMNAME --attach-os-disk $DISKNAME --os-type linux -l $LOCATION --size Standard_B4ms > /tmp/tempinstance
TEMPIP=$(grep privateip -i /tmp/tempinstance | awk -F '"' '{print $4}')
sleep 120
ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -i /root/sshkey root@$TEMPIP /root/ubuntu_installer_azure.sh $CUSTOM_UPDATE_VERSION
sleep 10
ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -i /root/sshkey root@$TEMPIP /root/softnas_update_$CUSTOM_UPDATE_VERSION.sh customupdate $CUSTOM_UPDATE_VERSION
echo "Rebooting Temporary Server for completing the softnas updates .. Please wait for 10 mins .. "
ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -i /root/sshkey root@$TEMPIP reboot
sleep 600
ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -i /root/sshkey root@$TEMPIP /root/deprovision.sh

#Capture Image
az vm deallocate --resource-group $RGROUP --name temp-$SOFTNAS_VMNAME
sleep 10
az vm generalize --resource-group $RGROUP --name temp-$SOFTNAS_VMNAME
sleep 10
az image create -g $RGROUP -n $IMAGENAME --source temp-$SOFTNAS_VMNAME --os-type Linux -l $LOCATION 
sleep 10
#Launch instance from the Image
az vm create -g $RGROUP -n Softnas-$SOFTNAS_VMNAME --image $IMAGENAME --admin-username softnas --admin-password $ADMINPASS --authentication-type password --size Standard_B4ms -l $LOCATION > /tmp/softnasip
set +ex
az vm open-port -g $RGROUP -n Softnas-$SOFTNAS_VMNAME --port 443 --priority 100 
az vm open-port -g $RGROUP -n Softnas-$SOFTNAS_VMNAME --port 80 --priority 110

#Terminate temporary instance
az vm delete --no-wait --yes -g $RGROUP -n temp-$SOFTNAS_VMNAME

PUBIP=$(grep -i publicip /tmp/softnasip | awk -F '"' '{print $4}')
TXT_C="tput setaf"    # set text color in BGR format
TXT_R="tput sgr0"     # reset text format
TXT_B="tput bold"     # set bold
TXT_U="tput sgr 0 1"  # set underlined
echo ""
echo "╔═════════════════════════════════════════════════════╗"
echo "║ Image Name     : $IMAGENAME                         "
echo "║ Login Username : softnas                            "
echo "║ Login Password : $ADMINPASS                         "
echo "║ Login URL : "$($TXT_C 4)"https://$PUBIP/"$($TXT_R)" "
echo "╚═════════════════════════════════════════════════════╝"
echo ""

}
# End of RUN ALL

RUN_ALL_AND_LOG 2>&1 | tee -a /root/build_azure.log

exit 0


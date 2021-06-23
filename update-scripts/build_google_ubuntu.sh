#!/bin/bash

# Script for building Google Softnas Image. 

# STEPS :
    # Builds zfs root ubuntu 20 OS
    # Install softnas base packages
    # Runs softnas update script
    # Creates a Google Image
    # Launch an instance from the image

# Requirements for running this script
    # Google instance with access to google cloud tools
    # Must be run this script from Ubuntu 20.04 LTS
    # Must be executed as root user

################################################################
echo "Started at `date` " > /root/build_google.log
# Read Arguments
TEMP=`getopt -o h,v:,u:,p:,t: --long help,version:,username:,password:,tenant: -n 'build_google_ubuntu.sh' -- "$@"`
eval set -- "$TEMP"

# extract options and their arguments into variables.
while true ; do
    case "$1" in
        -h|--help) echo "
NAME
    ./build_google_ubuntu.sh - Builds Google Softnas Image
SYNOPSIS
    ./build_google_ubuntu.sh [-v CustomUpdateVersion]

Requirements for running this script
    # Must be run this script from Ubuntu 20.04 LTS Google Instance with GCloud API Access
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
        --) shift ; break ;;
        *) echo "Incorrect Syntax!" ; 
        exit 1 ;;
    esac
done

if [ -z "$CUSTOM_UPDATE_VERSION" ] ; then
    echo "Custom update version not found."
    ./build_google_ubuntu.sh --help
    exit 1
fi

function RUN_ALL_AND_LOG ()
{

#Verify Gcloud API Access

echo "Verify Gcloud API Access ..."
if ! gcloud compute instances list ; then
	echo "GCloud API Access Failed. Please enable access and try again."
	exit 1
fi	

if ! lsblk | grep disk | grep sdb ; then
	echo "/dev/sdb Not Found. Please add a 100 Gb disk and try again."
	exit 1
fi	

URLBASE=https://www.softnas.com/software/softnas/customupdate
if ! wget $URLBASE/softnas_update_$CUSTOM_UPDATE_VERSION.sh ; then
 echo "Custom Update Version script not found - $URLBASE/softnas_update_$CUSTOM_UPDATE_VERSION.sh - FAILED"
 exit 1
fi

if ! wget https://www.softnas.com/software/softnas/ubuntu/ubuntu_installer_google.sh ; then
    echo "Ubuntu Softnas Installer script not found - https://www.softnas.com/software/softnas/ubuntu/ubuntu_installer_google.sh "
    exit 1
fi


##################################################################################################################

## BUILD UBUNTU 20 BOOT STRAPPER SCRIPT

echo '
#!/bin/bash

set -ex

# Update APT with new sources
apt-get update

# Do not configure grub during package install
echo "grub-pc grub-pc/install_devices_empty select true" | debconf-set-selections
echo "grub-pc grub-pc/install_devices select" | debconf-set-selections

# Install various packages needed for a booting system
DEBIAN_FRONTEND=noninteractive apt-get install -y linux-image-gcp linux-image-gcp grub-pc zfs-zed zfsutils-linux zfs-initramfs gdisk curl wget

# Set the locale to en_US.UTF-8
locale-gen --purge en_US.UTF-8
echo LANG="en_US.UTF-8" > /etc/default/locale
echo LANGUAGE="en_US:en" >> /etc/default/locale

# Install OpenSSH
apt-get install -y --no-install-recommends openssh-server

# Install GRUB
# shellcheck disable=SC2016
grub-probe /
grub-install /dev/sdb

# Configure and update GRUB
update-grub

# Install standard packages
DEBIAN_FRONTEND=noninteractive apt-get install -y ubuntu-standard cloud-init vim
groupadd ubuntu
useradd -g ubuntu -G ubuntu -m -d /home/ubuntu -s /bin/bash ubuntu

' > chroot-bootstrap.sh

chmod 755 chroot-bootstrap.sh

## END OF UBUNTU 20 BOOT STRAPPER SCRIPT
##

# Update apt and install required packages

set -ex
sudo apt-get update
DEBIAN_FRONTEND=noninteractive sudo apt-get install -y \
	zfs-zed zfsutils-linux zfs-initramfs zfs-dkms debootstrap gdisk perl jq

# Partition the new root EBS volume
sudo sgdisk -Zg -n1:0:4095 -t1:EF02 -c1:GRUB -n2:0:0 -t2:BF01 -c2:ZFS /dev/sdb

# Create zpool and filesystems on the new EBS volume
#umount /mnt
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
	/dev/sdb2

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
chmod 755 ubuntu_installer_google.sh
mv ubuntu_installer_google.sh /mnt/root/

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

#Configure Gcloud tools
for I in `df -h | grep loop | awk '{print $6}'`; do
        umount $I
done
source /etc/environment

echo "deb https://packages.cloud.google.com/apt cloud-sdk main" | sudo tee -a /etc/apt/sources.list.d/google-cloud-sdk.list 
curl https://packages.cloud.google.com/apt/doc/apt-key.gpg | sudo apt-key add - 
sudo apt-get install -y apt-transport-https ca-certificates gnupg
sudo apt-get update && sudo apt-get install -y google-cloud-sdk

#Create snapshot and instance for installing softnas
DISKNAME=$(curl -H Metadata-Flavor:Google http://metadata.google.internal/computeMetadata/v1/instance/disks/1/device-name)
ZONENAME=$(curl -l -H Metadata-Flavor:Google http://metadata.google.internal/computeMetadata/v1/instance/zone | awk -F '/' '{print $4}')
SOFTNAS_VMNAME=$(head /dev/urandom | tr -dc 0-9 | head -c 5 ; echo '')
IMAGENAME=softnas-$(head /dev/urandom | tr -dc a-z0-9 | head -c 13 ; echo '')
gcloud compute disks snapshot $DISKNAME --snapshot-names tempsnap1-$SOFTNAS_VMNAME --zone $ZONENAME
gcloud compute instances create --machine-type=n1-standard-2 --source-snapshot=tempsnap1-$SOFTNAS_VMNAME --zone=$ZONENAME --boot-disk-auto-delete sfttemp-$SOFTNAS_VMNAME > /tmp/tempinstance
##

TEMPIP=$(tail -n1 /tmp/tempinstance | awk '{print $4}')
sleep 120
ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -i /root/sshkey root@$TEMPIP /root/ubuntu_installer_google.sh $CUSTOM_UPDATE_VERSION
sleep 10
ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -i /root/sshkey root@$TEMPIP /root/softnas_update_$CUSTOM_UPDATE_VERSION.sh customupdate $CUSTOM_UPDATE_VERSION
echo "Rebooting Temporary Server for completing the softnas updates .. Please wait for 10 mins .. "
ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -i /root/sshkey root@$TEMPIP reboot
sleep 600
ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -i /root/sshkey root@$TEMPIP /root/deprovision.sh
#Create Image
gcloud beta compute machine-images create $IMAGENAME --source-instance sfttemp-$SOFTNAS_VMNAME --source-instance-zone $ZONENAME
sleep 10
gcloud beta compute instances create --machine-type=n1-standard-2 softnas-$SOFTNAS_VMNAME --zone=$ZONENAME --source-machine-image $IMAGENAME --tags http-server,https-server > /tmp/softnasip

set +ex
#Delete temp instances
echo "y" | gcloud compute instances delete sfttemp-$SOFTNAS_VMNAME --delete-disks=all --zone=$ZONENAME
echo "y" | gcloud compute snapshots delete tempsnap1-$SOFTNAS_VMNAME 
sleep 60

INSTANCEID=$(gcloud compute instances describe softnas-$SOFTNAS_VMNAME --zone=$ZONENAME | grep id | awk -F "'" '{print $2}')
PUBIP=$(tail -n1 /tmp/softnasip | awk '{print $5}')

#clear
TXT_C="tput setaf"    # set text color in BGR format
TXT_R="tput sgr0"     # reset text format
TXT_B="tput bold"     # set bold
TXT_U="tput sgr 0 1"  # set underlined
echo ""
echo "╔═════════════════════════════════════════════════════╗"
echo "║ Image Name     : $IMAGENAME                         "
echo "║ Login Username : softnas                            "
echo "║ Login Password : $INSTANCEID                        "
echo "║ Login URL : "$($TXT_C 4)"https://$PUBIP/"$($TXT_R)" "
echo "╚═════════════════════════════════════════════════════╝"
echo ""

}
# End of RUN ALL

RUN_ALL_AND_LOG 2>&1 | tee -a /root/build_google.log
echo "Completed at `date` " >> /root/build_google.log
exit 0


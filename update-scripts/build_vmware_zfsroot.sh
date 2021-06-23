#!/bin/bash

# Script for building Ubuntu ZFS root - VMWare Image. 

# Requirements for running this script
    # Must be run this script from Ubuntu 18 or 19
    # Must be executed as root user

################################################################


function RUN_ALL_AND_LOG ()
{

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
DEBIAN_FRONTEND=noninteractive apt-get install -y linux-image-generic linux-image-generic grub-pc zfs-zed zfsutils-linux zfs-initramfs gdisk curl wget

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
echo "ubuntu:Pass4W0rd" | chpasswd
echo "ubuntu ALL=(ALL:ALL) NOPASSWD: ALL" > /etc/sudoers.d/ubuntu
' > chroot-bootstrap.sh

chmod 755 chroot-bootstrap.sh

## END OF UBUNTU 20 BOOT STRAPPER SCRIPT
##

# Update apt and install required packages
DEBIAN_FRONTEND=noninteractive sudo apt-get install -y \
	zfs-zed zfsutils-linux zfs-initramfs zfs-dkms debootstrap gdisk perl jq

# Partition the new root EBS volume
sudo sgdisk -Zg -n1:0:4095 -t1:EF02 -c1:GRUB -n2:0:0 -t2:BF01 -c2:ZFS /dev/sdb

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
sudo cp /tmp/sources.list /mnt/etc/apt/sources.list

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

#Copy netplan yaml file for network settings
#cp /etc/netplan/50-cloud-init.yaml /mnt/etc/netplan/50-cloud-init.yaml

# This could perhaps be replaced (more reliably) with an `lsof | grep -v /mnt` loop,
# however in approximately 20 runs, the bind mounts have not failed to unmount.
sleep 10 

# Unmount bind mounts
sudo umount -l /mnt/dev
sudo umount -l /mnt/proc
sudo umount -l /mnt/sys

# Export the zpool
sudo zpool export rpool

}
# End of RUN ALL

RUN_ALL_AND_LOG 2>&1 | tee -a /root/build_zfsroot.log

exit 0


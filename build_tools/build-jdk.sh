#!/bin/bash

# For building Oracle JDK Apt package

# Syntax : ./build-jdk.sh 
#	   ./build-jdk.sh publish  ( For pushing to fussion apt repo - https://mirror.softnas.com/fuusion/aptrepo/ )

# Change below variables for building new version

JDKSOURCE="https://www.softnas.com/software/java/jdk/jdk-8u241-linux-x64.tar.gz"
JDKFILENAME="jdk-8u241-linux-x64.tar.gz"
VERSION="1.8.0-241"
DEBFNAME="fuusion-jdk-1.8.0-241.deb"
POSTCMD='ln -s /opt/jdk1.8.0_241 /opt/jdk'

#########

#Create a build dir
if [ -d build-jdk ]; then
 sudo rm -rf build-jdk
fi 
mkdir build-jdk
pushd build-jdk
 echo "Downloading jdk source from $JDKSOURCE  "
 wget $JDKSOURCE
 mkdir -p opt DEBIAN
 tar xzf $JDKFILENAME -C opt
 sudo chown root: -R opt

### 
echo "Creating debian build control file .... "
cat <<EOF > DEBIAN/control
Section: custom
Priority: optional
Maintainer: www.buurst.com
Version: $VERSION
Homepage: www.buurst.com
Package: fuusion-jdk
Architecture: amd64
Multi-Arch: foreign
Description: Clone of Oracle JDK 
EOF

###
echo "Creating post installation script ..."
cat <<EOF > DEBIAN/postinst
#!/bin/bash
$POSTCMD
sudo update-alternatives --install /usr/bin/java java /opt/jdk/bin/java 1
sudo update-alternatives --install /usr/bin/javac javac /opt/jdk/bin/javac 1
sudo update-alternatives --install /usr/bin/keytool keytool /opt/jdk/bin/keytool 1

if ! grep PATH /etc/environment | grep jdk ; then
 sed -e 's|PATH="\(.*\)"|PATH="/opt/jdk/bin:\1"|g' -i /etc/environment
fi
EOF

###
echo "Creating post remove script ... "
cat <<EOF > DEBIAN/postrm
#!/bin/bash
rm -f /opt/jdk
if [ ! -d /opt ]; then
 sudo mkdir /opt
fi
EOF

###
echo "Creating pre install script ... "
cat <<EOF > DEBIAN/preinst
#!/bin/bash
if [ -f /opt/jdk ] || [ -d /opt/jdk ] ; then
 sudo rm -rf /opt/jdk
fi
EOF

 chmod 755 DEBIAN/postrm DEBIAN/preinst DEBIAN/postinst
popd 

dpkg-deb --build build-jdk
mv build-jdk.deb $DEBFNAME


# Publish to fuusion repo
if [ "$1" == "publish" ]; then
 #upload to softnas.com server
 if [ -f $DEBFNAME ] ; then
	scp -P 25101 -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no \
	$DEBFNAME \
	fuusion@softnas.com:~/public_html/fuusion/aptrepo/amd64/
	if [ "$?" != "0" ]; then
		echo "Error : Publish Failed. "
		exit 1
	fi
 else
	echo "Publish Failed. File not found! "	 
 fi	 
fi	


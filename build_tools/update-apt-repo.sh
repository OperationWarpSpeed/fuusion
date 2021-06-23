#!/bin/sh

# working directory
if [ "$1" == "prod" ] ; then
	repodir=aptrepo
else
	repodir=aptrepo_"$1"
fi
KEYNAME="fuusion"
#export GNUPGHOME=gnupg

cd /home/fuusion/public_html/fuusion

if [ ! -d ${repodir} ] ; then
        echo ${repodir} not found
        exit
fi

cd ${repodir}

cd amd64
 for I in `ls` ; do
        if ! dpkg-sig -c $I ; then
                echo "Signing $I ........"
                dpkg-sig -k ${KEYNAME} --sign repo $I
        fi      
 done
cd ..

# create the package index
dpkg-scanpackages -m . > Packages
cat Packages | gzip -9c > Packages.gz

# create the Release file
PKGS=$(wc -c Packages)
PKGS_GZ=$(wc -c Packages.gz)
cat <<EOF > Release
Architectures: all
Date: $(date -R)
MD5Sum:
 $(md5sum Packages  | cut -d" " -f1) $PKGS
 $(md5sum Packages.gz  | cut -d" " -f1) $PKGS_GZ
SHA1:
 $(sha1sum Packages  | cut -d" " -f1) $PKGS
 $(sha1sum Packages.gz  | cut -d" " -f1) $PKGS_GZ
SHA256:
 $(sha256sum Packages | cut -d" " -f1) $PKGS
 $(sha256sum Packages.gz | cut -d" " -f1) $PKGS_GZ
EOF

rm -fr Release.gpg; gpg --default-key ${KEYNAME} -abs -o Release.gpg Release
rm -fr InRelease; gpg --default-key ${KEYNAME} --clearsign -o InRelease Release


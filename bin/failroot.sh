#/bin/sh
if [ "$(id -u)" == "0" ]; then
   echo "This script should NOT be run as root" 1>&2
   exit 1
fi

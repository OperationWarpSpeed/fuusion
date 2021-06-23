#/bin/sh
if ls /var/run/dhclient-*.pid > /dev/null 2>&1
then
# DHCP
echo "1"
else
# STATIC
echo "0"
fi


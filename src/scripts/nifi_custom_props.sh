#!/bin/bash

source /var/www/softnas/scripts/nifi_version.sh

NIFI_HOME="$1"

if [[ -z "${NIFI_HOME// }" ]]; then
	echo "Please specify location of NiFi Home directory"
	exit 1
fi
NIFI_PROPS="$NIFI_HOME/conf/nifi.properties"
BOOTSTRAP_CONF="$NIFI_HOME/conf/bootstrap.conf"
UPDATE_PROPS="/var/www/softnas/scripts/update_properties.sh"

# Retrieves local ip of the instance where nifi is installed
IPADDRS=$(ifconfig | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}')
IPADDR=$(echo "$IPADDRS" | head -n 1)
PUBLIC_IP=$(curl -k https://www.softnas.com/ip.php)
if cat /var/www/softnas/config/softnas.ini | grep -q amazon; then
    PRIV_DNS=$(curl -k http://169.254.169.254/latest/meta-data/hostname)
    PUB_DNS=$(curl -k http://169.254.169.254/latest/meta-data/public-hostname)
fi

# Make sure last character is new line
LAST_CHAR=$(tail -c 1 $NIFI_PROPS)
if [ "$LAST_CHAR" != "" ]; then
	echo >> $NIFI_PROPS
fi

# Set nifi.web.https.network.interface.* in nifi.properties
IFACES=""
for iface in `ls /sys/class/net`; do
    IFACES="$IFACES nifi.web.https.network.interface.$iface=$iface"
done

# Set site-to-site host
PREV_IP=$(grep 'nifi.remote.input.host=' $NIFI_PROPS | awk -F "=" '{ print $2 }')
if [ -z "$PREV_IP" ]; then
    SITE_TO_SITE="nifi.remote.input.host=$IPADDR"
fi

# Get other names, IPs or proxies that represent the node for nifi.web.proxy.host setting
ALL_PROXIES="$IPADDRS $PUBLIC_IP $PRIV_DNS $PUB_DNS"
PREV_PROXIES=$(grep 'nifi.web.proxy.host=' $NIFI_PROPS | awk -F "=" '{ print $2 }')
PREV_PROXIES_ARR=( $(echo "$PREV_PROXIES" | awk -F"," '{ for (i=1;i<=NF;i++) print $i; }') )
for PROXY in $ALL_PROXIES; do
    exists=false
    for PREV_PROXY in ${PREV_PROXIES_ARR[@]}; do
        [ "$PROXY" == "$PREV_PROXY" ] && exists=true && break
    done
    if [ $exists = "false" ]; then
        [ -z "$PREV_PROXIES" ] && PREV_PROXIES="$PROXY" || PREV_PROXIES="$PREV_PROXIES,$PROXY"
    fi
    echo "$PREV_PROXIES"
done
PROXIES="nifi.web.proxy.host=$PREV_PROXIES"

# Set default provenance configurations
IDX_ATTRS=$(grep 'nifi.provenance.repository.indexed.attributes=' $NIFI_PROPS | awk -F "=" '{ print $2 }')
if [ -z "$IDX_ATTRS" ]; then
    IDX_ATTRS='FlexFilesUUID, path'
else
    # Users may edit the indexed attributes, so check if they already appear in
    # the comma separated list, with possible white space.
    echo "$IDX_ATTRS" | grep "^\(.*,\)*\s*FlexFilesUUID\s*\(,.*\)*$" > /dev/null
    if [ $? -ne 0 ]; then
        IDX_ATTRS="$IDX_ATTRS,FlexFilesUUID"
    fi

    echo "$IDX_ATTRS" | grep "^\(.*,\)*\s*path\s*\(,.*\)*$" > /dev/null
    if [ $? -ne 0 ]; then
        IDX_ATTRS="$IDX_ATTRS,path"
    fi
fi
DEF_PROV_STORAGE_SIZE="5 GB"
DEF_PROV_STORAGE_TIME="180 days"
CUR_PROV_STORAGE_SIZE=$(grep 'nifi.provenance.repository.max.storage.size=' $NIFI_PROPS | awk -F "=" '{ print $2 }')
CUR_PROV_STORAGE_TIME=$(grep 'nifi.provenance.repository.max.storage.time=' $NIFI_PROPS | awk -F "=" '{ print $2 }')
[ "$CUR_PROV_STORAGE_SIZE" != "1 GB" ] && DEF_PROV_STORAGE_SIZE=$CUR_PROV_STORAGE_SIZE
[ "$CUR_PROV_STORAGE_TIME" != "24 hours" ] && DEF_PROV_STORAGE_TIME=$CUR_PROV_STORAGE_TIME

# Configure some default NiFi properties
# 18313 - Set also additional NiFi tuning properties
$UPDATE_PROPS $NIFI_PROPS \
    $IFACES \
    $SITE_TO_SITE \
    $PROXIES \
    nifi.version=$nifi_version \
    nifi.ui.banner.text="Fuusion Architect" \
    nifi.provenance.repository.indexed.attributes="$IDX_ATTRS" \
    nifi.bored.yield.duration="5 millis" \
    nifi.ui.autorefresh.interval="30 sec" \
    nifi.queue.swap.threshold=20000 \
    nifi.provenance.repository.query.threads=4 \
    nifi.provenance.repository.index.threads=2 \
    nifi.provenance.repository.index.shard.size="250 MB" \
    nifi.provenance.repository.implementation=org.apache.nifi.provenance.WriteAheadProvenanceRepository \
    nifi.provenance.repository.buffer.size=100000 \
    nifi.provenance.repository.max.storage.size="$DEF_PROV_STORAGE_SIZE" \
    nifi.provenance.repository.max.storage.time="$DEF_PROV_STORAGE_TIME"

$UPDATE_PROPS $BOOTSTRAP_CONF \
    java.arg.2="-Xms8g" \
    java.arg.3="-Xmx8g" \
    java.arg.7="-XX:ReservedCodeCacheSize=256m" \
    java.arg.8="-XX:CodeCacheMinimumFreeSpace=10m" \
    java.arg.9="-XX:+UseCodeCacheFlushing"

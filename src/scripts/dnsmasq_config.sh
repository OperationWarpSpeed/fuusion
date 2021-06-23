#!/bin/bash
##
## dnsmasq_config.sh - update dnsmasq resolver configuration and point system DNS to cache
## @author kashpande 2017-Jun
##

echo "DNS handler begin"
# detect the current latency for DNS and see if it meets the 0ms requirement
echo "Detecting current DNS latency.."
dig softnas.com 2>/dev/null > /dev/null # prime the DNS cache (if available)
# is DNS configured properly?
if [ $? -gt 0 ]; then
	echo "System DNS is not configured properly or server is not responding, cannot continue"
	exit 1
fi
DNS_LATENCY_MS=$(dig softnas.com 2>/dev/null| grep Query\ time: | awk '{print $4}') # retrieve latency of subsequent look-up
if [ $DNS_LATENCY_MS -lt 10 ]; then
	echo "DNS caching for current configuration meets 0ms requirement"
	exit
else
	echo "DNS caching is not implemented or does not meet 0ms requirement (${DNS_LATENCY_MS}ms)"
fi

# replace DNS configuration
echo "Configuring dnsmasq caching resolver.."
FILE=$(cat /etc/resolv.conf)

if [[ "$FILE" == "nameserver 127.0.0.1" ]]; then
	echo "File already modified"
else
	echo "Copying /etc/resolv.conf to /etc/resolv.dnsmasq"
	cp -v /etc/resolv.conf /etc/resolv.dnsmasq
	echo "Updating /etc/resolv.conf to point to 127.0.0.1 (dnsmasq)"
	echo "nameserver 127.0.0.1" > /etc/resolv.conf
fi

echo "Finished dnsmasq configuration."
service dnsmasq restart

service dnsmasq status

echo "Testing dnsmasq latency.."
dig softnas.com 2> /dev/null > /dev/null # prime dnsmasq cache
# did dnsmasq fail? we may revert the configuration then
if [ $? -gt 0 ]; then
	echo "dnsmasq seems to have failed. reverting system configuration.."
	mv -v /etc/resolv.dnsmasq /etc/resolv.conf
	echo "DNS handler exit with error state"
	exit 1
fi
echo "Initial dnsmasq lookup succeded"
DNSMASQ_LATENCY_MS=$(dig softnas.com 2> /dev/null | grep Query\ time: | awk '{print $4}') # retrieve latency of subsequent look-up
echo "dnsmasq latency: ${DNSMASQ_LATENCY_MS}ms"


echo "DNS handler complete"

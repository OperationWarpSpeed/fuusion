#!/bin/bash

# Script to check self signed ssl and to regenerate if the IP is changed - Fix for 2972

COMMON_NAME=$(openssl x509 -noout -subject -in /etc/pki/ca.crt | awk '{print $NF}')
IPADDR=$(ifconfig | grep "inet " | head -n 1 | awk '{print $2}')

CRT_MD5=$(openssl x509 -noout -modulus -in /etc/pki/ca.crt | openssl md5)
KEY_MD5=$(openssl rsa -noout -modulus -in /etc/pki/ca.key | openssl md5)

if [[ ("$COMMON_NAME" == "$IPADDR") && ("$CRT_MD5" == "$KEY_MD5") ]]; then
 echo "SSL - OK"
 exit 0
else
 echo "`date` : Monit : SSL - ERROR. Regenerating Self Signed SSL" >> /var/www/softnas/logs/snserv.log
 openssl req -x509 -sha256 -nodes -newkey rsa:2048 -out /etc/pki/ca.crt -keyout /etc/pki/ca.key -subj "/C=US/ST=TX/L=Houston/O=SoftNAS/CN=${IPADDR}" -days 365
 # regenerate ssh host key
 # rm -rf /etc/ssh/*key* # Disabled as this could affect HA connectivity
 # service sshd restart  
 service nginx restart
 wget --no-check-certificate -O - https://localhost/j.txt 1>/dev/null 2>/dev/null
 exit 1
fi


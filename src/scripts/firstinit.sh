#!/bin/bash
# firstinit.sh - one-time first initialization script for tasks that only get done one time after first boot
#

PROD_PATH="/var/www/softnas"
SCRIPTDIR="$PROD_PATH/scripts"
platform=$($SCRIPTDIR/which_host.sh)

# Fix for 3938
/usr/bin/monit stop VerifySSL

# retrieve first system IP address
export IPADDR=$(ifconfig | grep "inet " | head -n 1 | awk '{print $2}')
echo "Running one-time first SoftNAS initialization for ${IPADDR}..." >> /tmp/softnas.boot
# generate OpenSSL certificate
openssl req -x509 -sha256 -nodes -newkey rsa:2048 -out /etc/pki/ca.crt -keyout /etc/pki/ca.key -subj "/C=US/ST=TX/L=Houston/O=SoftNAS/CN=${IPADDR}" -days 365

#15264
openssl dhparam -out /etc/ssl/certs/dhparam.pem 2048

# regenerate ssh host key
rm -rf /etc/ssh/*key*
/etc/init.d/sshd restart
/etc/init.d/nginx restart

# aws instance creation report
retry=0
if [ "$($SCRIPTDIR/which_host.sh)" == "aws" ]; then
while [[ ${retry} -lt 5 ]]; do
    instance_id=`curl -s  http://169.254.169.254/latest/dynamic/instance-identity/document | grep instanceId | awk -F '"' '{print $4}'`
    if [[ ! -z ${instance_id} ]]; then
        /usr/bin/python /var/www/softnas/scripts/aws_report.py -i "${instance_id}" -r 'none' -t 'instance' >> /tmp/softnas.boot
        break
    fi
    sleep 1
    (( retry++ ))
done
fi

echo "`date` - One-time SoftNAS initialization completed." >> /tmp/softnas.boot

/usr/bin/monit start VerifySSL

#15577
echo "$(date +"%s")" > "$PROD_PATH/config/born"

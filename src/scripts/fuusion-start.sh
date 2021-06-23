#!/bin/bash

# This is used for starting all required services inside docker

#docker run -d --init -p 80:80 -p 443:443 --name fuusion fuusion:5.0.0.59 /usr/bin/fuusion

echo "Starting High Performance OpenSSH Server ... "
/etc/init.d/ssh start 
echo "Starting PHP FPM ... "
/etc/init.d/php7.4-fpm start 
echo "Starting MySQL Server ... "
/etc/init.d/mysql start 
echo "Running fuusion startup script ... "
/var/www/softnas/scripts/rc.startup.sh 
#/etc/init.d/nifi restart
#/etc/init.d/nifi-registry restart
echo "Starting UltraFast ...."
/etc/init.d/ultrafast restart 
/etc/init.d/monit start 
/etc/init.d/nginx stop 
# Run nginx in foreground
echo "Starting nginx service in foreground ..."
/usr/sbin/nginx -g 'daemon off;'


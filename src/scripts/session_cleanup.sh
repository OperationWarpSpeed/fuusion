#!/bin/bash
# clean up old PHP session files
find /tmp -type f -name sess_* | xargs rm -f > /dev/null 2>&1

# source the MySQL auth info and then clear DB sessions from storagecenter DB
#source /var/www/softnas/config/sqlpass.conf
#mysql -u storagecenter --password=$USERPASSWORD --execute "DELETE FROM sessions;" storagecenter
#mysql -u storagecenter --password=$USERPASSWORD --execute "DELETE FROM system_cache;" storagecenter

#!/bin/bash

#### Deploy DB ########
# written for SoftNAS #
#	by Kash Pande #
#######################

DB_HOST="localhost"
DB_USER="dbuser"
DB_PASS="dbpass"
DB_NAME="storagecenter"

## todo: look for preexisting db config file and use it for config

sed -i "s/REPLACE_HOST/$DB_HOST/" ruckusing.conf.php
sed -i "s/REPLACE_USER/$DB_USER/" ruckusing.conf.php
sed -i "s/REPLACE_PASS/$DB_PASS/" ruckusing.conf.php
sed -i "s/REPLACE_DB/$DB_NAME/" ruckusing.conf.php

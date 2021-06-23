#!/bin/bash
# $1 - email to
# $2 - assembla ticket number

if [ -z "$1" ]; then
	echo "Please specify email address"
	exit 1
fi
sudo curl https://www.softnas.com/getsupport/fuusion/ | sudo BRANCH=fuusion php -- $1 $2 2>&1

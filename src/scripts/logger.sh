#!/bin/bash

# This logger script mimics the set of php logging scripts such as log-it.php, logging.php and KLogger.php
# This script can be used by another script for logging purposes. The logging level is controlled by the 
# 'loglevel' setting under 'support' config in /var/www/softnas/config/softnas.ini.
# 
# Sample usage in another script:
# [samplescript.sh]
#   #!/bin/bash
#   source ./logger.sh
#   logger_initLogging ./test.log
#   logger_log Info "Test logging info"
# 
# If logging level in softnas.ini allows info logging, a log will be appended to test.log as below:
# Fri Mar 23 2018 09:08:55.152 - INFO --> Test logging info

# log levels
LOGGER_MAINT=0		 # Secret "Maintenance only" level logging
LOGGER_DEBUG=1		 # Most Verbose
LOGGER_INFO=2		 # ...
LOGGER_WARN=3		 # ...
LOGGER_ERROR=4 		 # ...
LOGGER_FATAL=5		 # Least Verbose
LOGGER_OFF=6		 # Nothing at all.
LOGGER_LEVEL=$LOGGER_OFF # Default is off
LOGGER_LOGFILE=""

function logger_convertLevel() {
	# $1 - Log leve such as Info, Debug, etc
	local level="$1"
	[ -z "$level" ] && level="off"
	level=$(echo $level | tr '[:upper:]' '[:lower:]')
	local levelnum=$LOGGER_OFF

	case "$level" in
		maint)
		    levelnum=$LOGGER_MAINT
		    ;;
		debug)
		    levelnum=$LOGGER_DEBUG
		    ;;
		info)
		    levelnum=$LOGGER_INFO
		    ;;
		warn)
		    levelnum=$LOGGER_WARN
		    ;;
		error)
		    levelnum=$LOGGER_ERROR
		    ;;
		fatal)
		    levelnum=$LOGGER_FATAL
		    ;; 
		*)
		    levelnum=$LOGGER_OFF
		    ;;
	esac
	echo $levelnum
}

function logger_initLogging() {
	# $1 - log file
	LOGGER_LOGFILE="$1"
	local level=$(crudini --get /var/www/softnas/config/softnas.ini support loglevel 2>/dev/null | tr -d \'\")
	LOGGER_LEVEL=$(logger_convertLevel $level)
}

function logger_getTimeLine() {
	# $1 log level
	local DATE=$(date);
	local MICRO=$(date -d "$DATE" "+%s" | head -c 3);
	local TIMELINE=$(date -d "$DATE" "+%a %b %d %G %H:%M:%S.$MICRO")
	local LOGGER_LEVELSTR="LOGGER_OFF"
	
	case "$1" in
		$LOGGER_MAINT)
		    LOGGER_LEVELSTR="MAINT"
		    ;;
		$LOGGER_DEBUG)
		    LOGGER_LEVELSTR="DEBUG"
		    ;;
		$LOGGER_INFO)
		   	LOGGER_LEVELSTR="INFO "
		    ;;
		$LOGGER_WARN)
		    LOGGER_LEVELSTR="WARN "
		    ;;
		$LOGGER_ERROR)
		    LOGGER_LEVELSTR="ERROR"
		    ;;
		$LOGGER_FATAL)
		    LOGGER_LEVELSTR="FATAL"
		    ;; 
		*)
		    LOGGER_LEVELSTR="OFF  "
		    ;;
	esac
	echo "$TIMELINE - $LOGGER_LEVELSTR -->"
}

function logger_log() {
	# $1 - Level such as Info, Debug, etc
	# $2 - Message to log
	local levelnum=$(logger_convertLevel $1)
	if [ $LOGGER_LEVEL -le $levelnum ] && [ $levelnum -ne $LOGGER_OFF ]; then
		local timeline=$(logger_getTimeLine $levelnum)
		if [ ! -z "$LOGGER_LOGFILE" ]; then
			echo "$timeline $2" >> $LOGGER_LOGFILE
		fi
	fi
}



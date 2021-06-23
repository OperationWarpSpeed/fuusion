#!/bin/bash
# Copyright (c) 2013-2016 SoftNAS, Inc.

PROD_PATH="/var/www/softnas"
source $PROD_PATH/scripts/logger.sh
logger_initLogging "$PROD_PATH/logs/flexfiles.log"
LOGIT="logger_log"

THIS_SCRIPT=`basename "$0"`

# If no parameters, display help
if [ -z $1 ]; then
	/var/www/softnas/scripts/nifi_ssh_utils.sh --help
	exit 0
fi

for I in `cat /etc/environment`; do
	export $I
done
source /etc/environment

############################################################
# Code for parsing command line arguments                  #
############################################################
TEMP=`getopt -o h,c,a,k,u:,p:,r:,e --long help,\
createKeys,addAuthorizedKey,addKnownHost,userName:,publicKey:,remoteNode:,echoOn -n 'nifi_ssh_utils.sh' -- "$@"`
eval set -- "$TEMP"

# extract options and their arguments into variables.
while true ; do
    case "$1" in
        -h|--help) echo "
NAME
	./nifi_ssh_utils.sh - Supports utility functions in exchanging ssh keys for nifi setup

SYNOPSIS
	./nifi_ssh_utils.sh	[ --createKeys | --addAuthorizedKey | --addKnownHost ]

	OPTIONS:
		--createKeys        - Create ssh keys for specified user saved at its default location
		--addAuthorizedKey  - Add a public key to be authorized to login as specified user
		--addKnownHost      - Save remote's key into specified user's known_hosts

	REQUIRED OPTIONS:
		--userName          - User that owns the authorized_keys and known_hosts to be updated
		--publicKey         - Remote user's public key that needs to be authorized
		--remoteNode        - Remote node
		
	OPTIONAL OPTIONS:

EXAMPLES
	1. Create ssh keys for softnas user into its default location (~/.ssh)
		./nifi_ssh_utils --createKeys --userName=softnas
	
	2. Add a public key to be authorized to login as softnas user
		./nifi_ssh_utils.sh --addAuthorizedKey --userName=softnas --publicKey='ssh-rsa AAAAB3Nza.... softnas@SoftNAS'

	3. Add a public key to be authorized to login as root user
		./nifi_ssh_utils.sh --addAuthorizedKey --userName=root --publicKey='ssh-rsa AAAAB3Nza.... root@SoftNAS'

	4. Add remote's key to softnas user's known_hosts
		./nifi_ssh_utils.sh --addKnownHost --userName=softnas --remoteNode=10.0.0.125

======================================== END =========================================
"
			HELP="TRUE"
			exit 0
			shift ;;
		-u|--userName)
			case "$2" in
				"") shift 2 ;;
				*) USER_NAME=$2 ;
				shift 2 ;;
			esac ;;
		-p|--publicKey)
			case "$2" in
				"") shift 2 ;;
				*) PUBLIC_KEY=$2 ;
				shift 2 ;;
			esac ;;
		-r|--remoteNode)
			case "$2" in
				"") shift 2 ;;
				*) REMOTE_NODE=$2 ;
				shift 2 ;;
			esac ;;
		-c|--createKeys) CREATE_KEYS="TRUE" ; shift ;;
		-a|--addAuthorizedKey) ADD_AUTH_KEY="TRUE" ; shift ;;
		-k|--addKnownHost) ADD_KNOWN_HOST="TRUE" ; shift ;;
		-e|--echoOn) ECHO_ON="TRUE" ; shift ;;
		--) shift ; break ;;
		*) echo "Incorrect Syntax!" ; 
		exit 1 ;;
	esac
done

function logger() 
{
	# $1 = Log type
	# $2 = Message

	if [ "$(id -u)" == "0" ]; then
		$LOGIT $1 "$2"
	fi
}

function createKeysFunc() 
{
	[ -z "$USER_NAME" ] && { logger Error "Username is empty!"; exit 1; }
	AUTH_KEYS_DIR="/root/.ssh"
	[ "$USER_NAME" != "root" ] && AUTH_KEYS_DIR="/home/$USER_NAME/.ssh"
	mkdir -p $AUTH_KEYS_DIR
	[ ! -f $AUTH_KEYS_DIR/id_rsa ] && ssh-keygen -f $AUTH_KEYS_DIR/id_rsa -t rsa -N ''
	[ ! -f $AUTH_KEYS_DIR/id_rsa ] && { logger Error "Failed to create ssh keys!"; exit 1; }
	[ "$USER_NAME" != "root" ] && chown -R $USER_NAME:$USER_NAME $AUTH_KEYS_DIR
	logger Info "Keys for $USER_NAME created"
}

function createKeys() 
{	
	logger Info "Executing $THIS_SCRIPT --createKeys --userName=$USER_NAME ..."
	createKeysFunc
	logger Info "Done creating ssh keys for user $USER_NAME ($?)"
}

function addAuthorizedKey()
{
	SHORT_KEY="$(echo $PUBLIC_KEY | head -c 30)"
	logger Info "Executing $THIS_SCRIPT --addAuthorizedKey --userName=$USER_NAME --publicKey='$SHORT_KEY' ..."
	createKeysFunc
	[ ! -f $AUTH_KEYS_DIR/authorized_keys ] && touch $AUTH_KEYS_DIR/authorized_keys
	cat $AUTH_KEYS_DIR/authorized_keys | grep "$PUBLIC_KEY" || echo "$PUBLIC_KEY" >> $AUTH_KEYS_DIR/authorized_keys
	cat $AUTH_KEYS_DIR/authorized_keys | grep "$PUBLIC_KEY" &>/dev/null || { logger Error "Failed in authorizing key!"; exit 1; }
	logger Info "Done authorizing key for user $USER_NAME ($?)"
}

function addKnownHost()
{
	logger Info "Executing $THIS_SCRIPT --addKnownHost --userName=$USER_NAME --remoteNode=$REMOTE_NODE ..."
	createKeysFunc
	[ ! -f $AUTH_KEYS_DIR/known_hosts ] && touch $AUTH_KEYS_DIR/known_hosts
	ssh-keygen -R $REMOTE_NODE -f $AUTH_KEYS_DIR/known_hosts
	ssh-keyscan -t rsa $REMOTE_NODE 2>&1 >> $AUTH_KEYS_DIR/known_hosts || { logger Error "Failed to verify ssh peer!"; exit 1; }
	logger Info "Done adding known host ($?)"
}

# Main decision starts here
if [ "$CREATE_KEYS" == "TRUE" ]; then
	FUNC="createKeys"
elif [ "$ADD_AUTH_KEY" == "TRUE" ]; then
	FUNC="addAuthorizedKey"
elif [ "$ADD_KNOWN_HOST" == "TRUE" ]; then
	FUNC="addKnownHost"
else
	echo "Invalid options specified!"
	logger Error "Invalid options specified!"
	exit 1;
fi

# Redirect output to /dev/null if specified
if [ "$ECHO_ON" != "TRUE" ]; then
	$FUNC > /dev/null 2>&1
else
	$FUNC
fi

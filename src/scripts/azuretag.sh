#!/bin/bash

# 6761 - Resource Tagging - Apply tag to VM

if [ `/var/www/softnas/scripts/which_host.sh` != "azure" ]; then
    exit 0
fi

PHP="/usr/bin/php"
PROD_PATH="/var/www/softnas"
LOGIT="php $PROD_PATH/snserver/log-it.php snserv.log"
SCRIPT_PATH="/var/www/softnas/scripts"
AZURE_CMD="/var/www/softnas/scripts/azwrapper.sh"
CONFIG_FILE="/var/www/softnas/config/azure.ini"
AZDISK_INI="/var/www/softnas/config/azdisk.ini"
LOG_DIR="/var/www/softnas/logs"


CMD_ENCRYPT="/var/www/softnas/snserver/cmd_encrypt.php" # php script to encrypt/decrypt string. Usage "php cmd_encrypt.php [ method [encryption_key] ] ciphertext*

function read_ini_var
{
        local line=`grep "^\s*$1" $2`
        IFS="="
        read -r VAR VAL <<< "${line}"
        IFS="${IFS_OLD}"
        VAL=$(echo $VAL | sed -e 's/^\s\+//' -e 's/\s\+$//' -e 's/^"//' -e 's/"$//')
        echo $VAL
}
function decrypt
{
        local var="$1"
        var=`$PHP $CMD_ENCRYPT decrypt $var`
        echo "$var"
}
function encrypt
{
        local var="$1"
        var=`$PHP $CMD_ENCRYPT encrypt $var`
        echo "$var"
}


  # Check Keys avaialble or not # required for creating buckets
AZUSER=`grep azureUserName $CONFIG_FILE | cut -d "\"" -f2`
if [ ! -z $AZUSER ]; then
    ObfuscatedKeys=$(read_ini_var obfuscated $CONFIG_FILE)  # flag added to know aws keys was encrypted or not, used for migration for old installtion
    USERNAME=$(read_ini_var azureUserName $CONFIG_FILE)
    PASSWORD=$(read_ini_var azurePassword $CONFIG_FILE)
    TENANT=$(read_ini_var azureServiceTenant $CONFIG_FILE)
    VMNAME=$(read_ini_var azureVMName $CONFIG_FILE)
    RGROUP=$(read_ini_var azureResourceGroup $CONFIG_FILE)
    GOVCLOUD=$(read_ini_var azureGovCloud $CONFIG_FILE)
    ############################################################
    # Decrypt aws keys if they are encrypted
    if [[ "$ObfuscatedKeys" == "true" ]]; then
      USERNAME=$(decrypt $USERNAME)
      PASSWORD=$(decrypt $PASSWORD)
    fi
else
    ${LOGIT} WARN "Azure Login Details Not Found. Tagging Failed."
    exit 1
fi

function validate_command_status
{
    if [ "$?" -eq "0" ]; then
        ${LOGIT} Info "Login Check Passed"
    else
        ${LOGIT} Error "Login Check - Failed .$1"
        exit 1
    fi
}

if [[ "$GOVCLOUD" == "true" ]] || [[ "$GOVCLOUD" == "1" ]] ; then
    ${LOGIT} Debug "Azure Gov Cloud Login"
    $AZURE_CMD cloud set --name AzureUSGovernment
    if [[ $USERNAME == http://* ]] || [[ $USERNAME == https://* ]] ; then
	    ${LOGIT} Debug "Using Service Principal"
	    $AZURE_CMD login --service-principal -u $USERNAME -p $PASSWORD --tenant $TENANT
        validate_command_status 301
    else
        $AZURE_CMD login -u $USERNAME -p $PASSWORD
        validate_command_status 302
    fi
else
    $AZURE_CMD cloud set --name AzureCloud
    if [[ $USERNAME == http://* ]] || [[ $USERNAME == https://* ]] ; then
	    ${LOGIT} Debug "Using Service Principal"
	    $AZURE_CMD login --service-principal -u $USERNAME -p $PASSWORD --tenant $TENANT
        validate_command_status 303
    else
        $AZURE_CMD login -u $USERNAME -p $PASSWORD
        validate_command_status 304
    fi
fi

# Apply tag to VM
$AZURE_CMD vm show -n $VMNAME -g $RGROUP --query tags.provider | grep EB134AC5-695C-4E54-BBB6-34CE4B6F53FE
if [ "$?" != "0" ]; then
    $AZURE_CMD vm update -n $VMNAME -g $RGROUP --set tags.provider=EB134AC5-695C-4E54-BBB6-34CE4B6F53FE
fi


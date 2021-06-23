#!/bin/bash

# traffic-tests.sh
# Buurst Inc. 2021

# Ethernet interface to apply tc rules on
ETH=$1

# Test scenario to execute
TST=$2

# Location of traffic shaper
TC=/var/www/softnas/scripts/em.sh

# Functions
#

# Turn off traffic shaping on CTRL-C
function ctrl_c() {
        echo '** Trapped CTRL-C'
	${TC} tc_del_settings ${ETH}
	exit 1
}

# Print parameter usage
function usage {
    echo "Usage: $(basename $0) <eth> <test#>" 2>&1
    echo '   <eth> Ethernet interface to apply shaping on'
    echo '   <test#> Test scenario to execute'
    echo '      1 = 0.2%/1%/5%/20% loss @ 500ms delay tests'       
    echo '      2 = 0.2%/1%/5%/20% loss @ 1000ms delay tests'       
    echo '      3 = 0.2%/1%/5%/20% loss @ 2000ms delay tests'       
    echo '      4 = Interruption test 1 - 2mins @ 1% / 1min @ 100%'       
    echo '      5 = Interruption test 2 - 7mins @ 1% / 5mins @ 100%'       
    echo ''
    echo 'Example:  ./traffic-tests.sh eth0 4'
    echo ''
    exit 1
}

# Event logging
logit() {
    echo
    echo "`date "+%Y-%m-%d %H:%M:%S"` [${1}]: ${2}"
}

# Test scenarios
#

# 0.2%/1%/5%/20% loss @ 500ms delay tests
function runtest1 {
    x=1
    itr=2
    while [ $x -lt $itr ]; do
        logit INFO "Running 20 mins of 0.2% loss and 500ms delay"
        ${TC} tc_loss $ETH 0.2% 1200 500
        wait
    
        logit INFO "Running 20 mins of 1% loss and 500ms delay"
        ${TC} tc_loss $ETH 1% 1200 500
        wait

        logit INFO "Running 20 mins of 5% loss and 500ms delay"
        ${TC} tc_loss $ETH 5% 1200 500
        wait

        logit INFO "Running 20 min of 20% loss and 500ms delay"
        ${TC} tc_loss $ETH 20% 1200 500
        wait

	logit INFO "Completing iteration $x of $itr"

	x=$((x+1))
    done
    logit INFO "Test scenario 1 has finished"
}

# 0.2%/1%/5%/20% loss @ 1000ms delay tests
function runtest2 {
    x=1
    itr=2
    while [ $x -lt $itr ]; do
        logit INFO "Running 20 mins of 0.2% loss and 1000ms delay"
        ${TC} tc_loss $ETH 0.2% 1200 1000
        wait
    
        logit INFO "Running 20 mins of 1% loss and 1000ms delay"
        ${TC} tc_loss $ETH 1% 1200 1000
        wait

        logit INFO "Running 20 mins of 5% loss and 1000ms delay"
        ${TC} tc_loss $ETH 5% 1200 1000
        wait

        logit INFO "Running 20 min of 20% loss and 1000ms delay"
        ${TC} tc_loss $ETH 20% 1200 1000
        wait

	logit INFO "Completing iteration $x of $itr"

	x=$((x+1))
    done
    logit INFO "Test scenario 2 has finished"
}

# 0.2%/1%/5%/20% loss @ 2000ms delay tests
function runtest3 {
    x=1
    itr=2
    while [ $x -lt $itr ]; do
        logit INFO "Running 20 mins of 0.2% loss and 2000ms delay"
        ${TC} tc_loss $ETH 0.2% 1200 2000
        wait
    
        logit INFO "Running 20 mins of 1% loss and 2000ms delay"
        ${TC} tc_loss $ETH 1% 1200 2000
        wait

        logit INFO "Running 20 mins of 5% loss and 2000ms delay"
        ${TC} tc_loss $ETH 5% 1200 2000
        wait

        logit INFO "Running 20 min of 20% loss and 2000ms delay"
        ${TC} tc_loss $ETH 20% 1200 2000
        wait

	logit INFO "Completing iteration $x of $itr"

	x=$((x+1))
    done
    logit INFO "Test scenario 3 has finished"
}

# Interruption test 1
function runtest4 {
    x=1
    itr=10
    while [ $x -lt $itr ]; do
        logit INFO "Running 2 mins of 1% loss and 1000ms delay"
        ${TC} tc_loss $ETH 1% 120 1000
        wait
    
        logit INFO "Running 1 mins of 100% loss and 1000ms delay"
        ${TC} tc_loss $ETH 100% 60 1000
        wait

	logit INFO "Completing iteration $x of $itr"

	x=$((x+1))
    done
    logit INFO "Test scenario 4 has finished"
}

# Interruption test 2
function runtest5 {
    x=1
    itr=10
    while [ $x -lt $itr ]; do
        logit INFO "Running 7 mins of 1% loss and 1000ms delay"
        ${TC} tc_loss $ETH 1% 420 1000
        wait
    
        logit INFO "Running 1 mins of 100% loss and 1000ms delay"
        ${TC} tc_loss $ETH 100% 300 1000
        wait

	logit INFO "Completing iteration $x of $itr"

	x=$((x+1))
    done
    logit INFO "Test scenario 5 has finished"
}

# Error checking
#

# Exit with usage if no params
if [[ ${#} -eq 0 ]]; then
   usage
fi

# Check for valid Ethernet interface
/usr/sbin/ifconfig ${ETH} >/dev/null 2>&1
if [ $? -eq 1 ]; then
    echo 'Valid Ethernet interface not specified'
    echo ''
    usage
fi

# Check for test scenario 
if [ -z "$TST" ]; then
       echo 'Please specify which test scenario to run.'
       echo '  1 = 500ms delay tests'       
       echo '  2 = 1000ms delay tests'       
       echo '  3 = 2000ms delay tests'       
       echo '  4 = Interruption test 1 - 2mins @ 1% / 1min @ 100%'       
       echo '  5 = Interruption test 2 - 7mins @ 1% / 5mins @ 100%'       
       exit 1
fi

# trap ctrl-c and call ctrl_c()
trap ctrl_c INT

# Main
#
${TC} disable_nic_opt ${ETH}
runtest${TST}

#!/bin/bash

##############################################
##             Script for Proxy
##   Nate Jennings
##   9/29/14
##############################################

# -h = Proxy IP
# -p = Proxy Port
## Optional ##
# -u = Proxy UserName
# -w = Proxy Password
# -e = Comma-separated list of excluded hosts for no_proxy

##############################################
containsElement () {
    local e
    for e in "${@:2}"; do
        [[ "$e" == "$1" ]] && return 0
    done
    return 1
}

if [ $1 = "remove" ]
 then
    rm -Rr /etc/environment && touch /etc/environment
    rm -f /etc/apt/apt.conf.d/02aptproxy
        echo "Proxy Removed"
        exit
fi

if [[ "${1}" == "noproxy" ]]; then
    if [[ ! -z $(cat /etc/environment | grep http_proxy) && -z $(cat /etc/environment | grep ${2}) ]]; then
        sed -i /no_proxy/d /etc/environment
        if [[ -z ${no_proxy} ]]; then
            no_proxy="${2}"
        else
            no_proxy="${2},${no_proxy}"
        fi
        export no_proxy
        echo no_proxy="${no_proxy}" >> /etc/environment
    fi
    exit
fi
if [[ "${1}" == "rem_noproxy" ]]; then
    if [[ ! -z $(cat /etc/environment | grep http_proxy) && ! -z $(cat /etc/environment | grep ${2}) ]]; then
        sed -i -r s/${2},// /etc/environment
        source /etc/environment
        export no_proxy
    fi
    exit
fi

while getopts ":h:p:u:w:e:" opt; do
    case ${opt} in
        h)
            proxy_host=${OPTARG}
            ;;
        p)
            proxy_port=${OPTARG}
            ;;
        u)
            proxy_user=${OPTARG}
            ;;
        w)
            proxy_pass=${OPTARG}
            ;;
        e)
            proxy_excl=${OPTARG}
            ;;
        \?)
            echo "Invalid option: -$OPTARG" >&2
            echo "example: proxy -h 10.0.0.1 -p 8080 -u SoftNAS -w Pass4W0rd -e localhost,.localdomain"
            exit 1
            ;;
    esac
done

WHICHHOST=`/var/www/softnas/scripts/which_host.sh`
eth0_ip=`ip a show eth0 | grep "inet " | awk '{print $2}' | cut -d "/" -f1`
no_proxy_aws="localhost,127.0.0.1,${eth0_ip},localaddress,.localdomain.com,169.254.169.254"
no_proxy_reg="localhost,127.0.0.1,${eth0_ip},localaddress,.localdomain.com"
if [ "$WHICHHOST" == "aws" ]; then
    no_proxy="${no_proxy_aws}"
else
    no_proxy="${no_proxy_reg}"
fi

if [[ ! -z ${proxy_excl} ]]; then
    no_proxy_arr=(${no_proxy//,/ })
    for excl in ${proxy_excl}; do
        if ! containsElement "${excl}" "${no_proxy_arr[@]}"; then
            no_proxy="${no_proxy},${excl}"
            no_proxy_arr=(${no_proxy//,/ })
        fi
    done
fi

if [[ ! -z ${proxy_user} ]]; then
    proxy_string=${proxy_user}:${proxy_pass}@${proxy_host}:${proxy_port}
else
    proxy_string=${proxy_host}:${proxy_port}
fi

export no_proxy
echo no_proxy="${no_proxy}" >> /etc/environment

export http_proxy="http://${proxy_string}"
export https_proxy=$http_proxy
#  export ftp_proxy=$http_proxy
#  export rsync_proxy=$http_proxy
echo http_proxy="http://${proxy_string}" >> /etc/environment
echo https_proxy=$http_proxy >> /etc/environment
#  echo ftp_proxy=$http_proxy >> /etc/environment
#  echo rsync_proxy=$http_proxy >> /etc/environment

echo "
Acquire::http::Proxy $proxy_string ;
Acquire::https::Proxy $proxy_string ;" > /etc/apt/apt.conf.d/02aptproxy

echo "Proxy environment variable set."

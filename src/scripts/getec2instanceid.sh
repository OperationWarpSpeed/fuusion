#!/bin/bash
if [ -f "/usr/local/bin/aws" ]; then
    wget -qO- http://169.254.169.254/latest/meta-data/instance-id
fi


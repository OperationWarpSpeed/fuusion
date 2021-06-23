#!/bin/bash
# rc.shutdown.sh - Clean shutdown of SoftNAS file services
#
# Copyright (c) SoftNAS, Inc. - All Rights Reserved
#

# Stop nifi if running
logger -t softnas "Stopping nifi service"
/var/www/softnas/scripts/nifi-service.sh stop

# Stop ultrafast if running
logger -t softnas "Stopping ultrafast service"
if [ -f /opt/ultrafast/bin/ultrafast ]; then
	/opt/ultrafast/bin/ultrafast stop
fi

logger -t softnas "Stopping crond to stop SnapReplicate"
systemctl stop cron

logger -t softnas "Synchronizing file systems"
sync

if [[ -f /etc/softnas/bootinit.completed ]]; then
	rm -f /etc/softnas/bootinit.completed
fi

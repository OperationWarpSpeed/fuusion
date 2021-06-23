#!/bin/bash

# Starts/stops flexfiles services. Exit non-zero if unsuccessful
# $1 - action to do on specified service/s (start or stop)
# $2 - service/s (nifi, ultrafast, or all)
# $3 - other params
#    - 1 = force stop if snap status file exist
#    - 2 = force stop if snaprep/ha role is target
#    - 3 = ignore snap status file or snap role target
#    - 4 = force stop if snapha role is source and status is active

# validate params
case $1 in
	start|stop)
		case $2 in
			nifi|ultrafast|all)
				case $3 in
					1|2|3|4) ;;
					*) echo "Invalid other param"; exit 255; ;;
				esac ;;
			*) echo "Invalid service param"; exit 255; ;;
		esac ;;
	*) echo "Invalid action param"; exit 255; ;;
esac

EXIT_CODE=0
EXIT_MSG="Operation successful"
CMD=$1

SCRIPTS_DIR=$(dirname $0)
$SCRIPTS_DIR/nifi-service.sh $CMD >/dev/null 2>&1
systemctl $CMD ultrafast

echo $EXIT_MSG
exit $EXIT_CODE

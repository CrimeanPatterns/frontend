#!/bin/sh

AWPATH='/www/awardwallet/app/config/supervisor/*.conf' 

[ "$(whoami)" != "root" ] && echo "This script shoul be stared with root privileges" && exit 1

case "$1" in
    start|stop)
        if ! ls -1 "$AWPATH" > /dev/null 2>&1; then 
            echo "Can not find config files with path $AWPATH"
            exit 2
        fi
        
        cat "$AWPATH" | sed '/^\[/!d;s/\[program:/supervisorctl '"$1"' /;s/\]/:/'
    ;;
    *)
        echo "Starting or stopping all supervisor workers from $AWPATH"
        echo
        echo "Usage:"
        echo "  $0 stop"
        echo "  $0 start"
        exit 3
    ;;
esac

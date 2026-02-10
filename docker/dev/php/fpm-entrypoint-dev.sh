#!/bin/bash

LOG_PIPE=$1

sudo chown -R user:user /run/php
mkfifo -m 600 $LOG_PIPE
cat <> $LOG_PIPE 1>&2 &

POOL_CONFIG="-y $2"
PHP_OPTION=''

if [ ! -z "$3" ]; then
    PHP_OPTION="-d $3"
fi

exec /usr/sbin/php-fpm${PHP_VERSION} -F $POOL_CONFIG $PHP_OPTION
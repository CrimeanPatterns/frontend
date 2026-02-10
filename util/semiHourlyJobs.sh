#!/bin/bash -xv

cd /www/awardwallet
date
err=''

php app/console aw:check-sla -vv || err="$err\ncheckSla"
php app/console aw:provider:check-health -vv || err="$err\naw:provider:check-health"
php app/console aw:remove-old-sessions 10800  -vv || err="$err\naw:remove-old-sessions"
php app/console aw:send-email:retention-user -vv || err="$err\naw:send-email:retention-user"
php app/console aw:clear-unlinked-card-images -vv || err="$err\naw:clear-unlinked-card-images"
php app/console aw:detect-credit-cards -vv || err="$err\naw:aw:detect-credit-cards"

[ -n "$err" ] && echo -e "Something is failed:\n$err" && exit 1
exit 0


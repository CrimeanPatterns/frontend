#!/bin/bash -xv

cd /www/awardwallet || exit 1
err=''

app/console aw:business:keep-upgraded -vv || err="$err\nbusiness:keep-upgraded"
app/console aw:iap:check-subscriptions ios -vv || err="$err\niap:check-subscriptions ios"
app/console aw:billing:resume-paypal -vv || err="$err\nbilling:resume-paypal"
app/console aw:iap:check-subscriptions android -vv || err="$err\niap:check-subscriptions android"
app/console aw:billing:download-android-payments -vv --apply-small-date-fixes --apply-refunds --apply-recovers --lastDays 90 || err="$err\nbilling:download-android-payments"
app/console aw:billing:sync-stripe-transactions -vv --apply-refunds --lastDays 3 || err="$err\aw:billing:sync-stripe-transactions"
app/console aw:bill-cards -vv || err="$err\nbill-cards"
app/console aw:fix-plus-expiration -vv || err="$err\nfix-plus-expiration"
app/console aw:check-paypal-profiles -vv --free --remove || err="$err\ncheck-paypal-profiles"
app/console aw:cancel-failed-subscriptions -vv || err="$err\ncancel-failed-subscriptions"
app/console aw:email:expire-awplus -vv --expiresSoon || err="$err\nexpire-awplus"
app/console aw:cancel-at201-access -vv --apply || err="$err\ncancel-at-201"

if [[ "$err" == "" ]]; then
  echo "success"
  exit 0
else
  echo -e "There are failures:\n$err"
  exit 1
fi

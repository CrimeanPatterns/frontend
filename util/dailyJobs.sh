#!/bin/bash -xv

cd /www/awardwallet || exit 1
err=''

php util/deleteOld.php || err="$err\ndeleteOld"
app/console aw:update-site-statistics || err="$err\nupdate-site-statistics"
php util/importFlights.php || err="$err\nimportFlights"
util/buildStats.php || err="$err\nbuildStats"

app/console aw:update-airports -vv || err="$err\nupdate-airports"
app/console aw:update-aircrafts -vv || err="$err\nupdate-aircrafts"
app/console aw:update-aircrafts-icao -vv || err="$err\nupdate-aircrafts-icao"
app/console aw:update-airlines -vv || err="$err\nupdate-airlines"
app/console aw:update:ticket-prefix -vv || err="$err\nupdate-prefix"
app/console aw:credit-cards:update-non-affiliate-disclosure -vv || err="$err\nupdate-cards-non-aff-disclosure"

app/console aw:booking:update-status -vv || err="$err\nbooking-status"
php util/update/updateOffers.php || err="$err\nupdate-offers"
php util/update/updateAAShare.php || err="$err\nupdate-aa"
app/console aw:user:update-is-us -vv || err="$err\nuser-is-us"
app/console aw:blog:sync-link-click -vv || err="$err\nblog-sync-link-click"

# decided never delete old plns. php archiveOldPlans.php || err="$err\narchive-old"
php util/update/updateNumberAccounts.php || err="$err\nupdate-na"
# should be run before bgcheck - bgcheck relies on mailbox count
app/console aw:scanner:cache-mailbox-info -vv || err="$err\naw:scanner:cache-mailbox-info"
app/console aw:update-bgcheck-accounts -vv || err="$err\nbgcheck"
app/console aw:email:check -vv || err="$err\nemail-check"
app/console aw:providers:update-stats -vv || err="$err\nupdate-stats"
app/console aw:clear-diff -vv || err="$err\nclear-diff"
app/console aw:users:update-owned -vv || err="$err\nupdate-owned"
app/console aw:cardspend:sent-email -vv
# PHP Notice:  Unknown: System Error (Failure) (errflg=2) in Unknown on line 0
app/console aw:contactusanswers:check -vv || err="$err\ncontactus"
app/console aw:flightinfo:update -vv || err="$err\nflightinfo"
# ParsedEmailFormatCommand: invalid languages ("fr") in \AwardWallet\Engine\sncf\Email\TicketPdf2017Fr
php -d memory_limit=512M app/console aw:email-parsing:formats -vv || err="$err\nparsing-formats"
app/console aw:update-popularity -vv || err="$err\nupdate-pop"
app/console aw:booking:unsharing -vv || err="$err\nbooking-unsharing"

app/console aw:scanner:notify-not-connected -vv || err="$err\naw:scanner:notify-not-connected"
# app/console aw:trip-alerts manage-subscriptions -vv
php app/console aw:record-site-stats -vv || err="$err\naw:aw:record-site-stats"

# slow
app/console aw:update-aa-membership -vv || err="$err\nupdate-aa-mem"
app/console aw:update-united-questions -vv || err="$err\nupdate-united"
php -d memory_limit=2000M app/console aw:calc-mile-value -v --update-from-db --days 90 --extra-sources || err="$err\ncalc-mile-value-db"
# async now php -d memory_limit=2048M app/console aw:calc-mile-value -v  --extra-sources || err="$err\ncalc-mile-value"
# async now app/console aw:calc-hotel-point-value || err="$err\ncalc-hotel-point-value"
app/console aw:update-transfer-times -vv || err="$err\nupdate-transfer-times"
# too slow
#app/console aw:travel-statistics -vv || err="$err\ntravel-statistics-data"
app/console aw:fill-hotels --mark-mismatched-brands-as-errors || err="$err\nfill-hotels"
app/console aw:hotels-fill-link -vv || err="$err\nhotels-fill-link"
app/console aw:update-airport-popularity -vv || err="$err\naw:update-airport-popularity"
app/console aw:cleaning-lounges -vv || err="$err\naw:cleaning-lounges"
app/console aw:terminal-stats --clear-terminals -vv || err="$err\naw:terminal-stats"
app/console aw:update-ra-flight-stat -vv || err="$err\naw:update-ra-flight-stat"
app/console aw:rephrase-lounge -vv || err="$err\naw:rephrase-lounge"
app/console aw:structuring-opening-hours -vv || err="$err\naw:structuring-opening-hours"
app/console aw:ra:flight-sync -vv || err="$err\naw:ra:flight-sync"
app/console aw:ra:flight-search -vv || err="$err\naw:ra:flight-search"
app/console aw:ra:flight-clean -vv || err="$err\naw:ra:flight-clean"
app/console aw:page-visit:clear -vv || err="$err\naw:page-visit:clear"
app/console aw:tripit:run-import -vv --weeks=1 || err="$err\naw:tripit:run-import"
app/console aw:send-email:subscription-renewal-reminder -vv || err="$err\naw:send-email:subscription-renewal-reminder"
app/console aw:scan-received-total -vv || err="$err\naw:scan-received-total"

if [[ "$err" == "" ]]; then
  echo "success"
  exit 0
else
  echo -e "There are failures:\n$err"
  exit 1
fi

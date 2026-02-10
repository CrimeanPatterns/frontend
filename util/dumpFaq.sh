#!/bin/sh

set -eux pipefail

DUMP=/tmp/faq.sql
mysqldump --defaults-file=~/vars/mysql/frontend.cnf awardwallet Currency Faq FaqCategory > "$DUMP"
scp "$DUMP" staging2:/tmp/faq.sql

stbase=`ssh staging2 cat /www/staging/shared/configs/parameters.yml | sed '/^[ \t]*database_name:/!d;s/^.*database_name:[ ]*//'`
cnames=`ssh staging2 docker ps --format '{{.Names}}' | sed '/staging2/d'`

ddesktop_db=`echo "$cnames" | grep dev-desktop.*mysql`
staging_db=`echo "$cnames" | grep staging.*mysql`

ddesktop=`echo "$cnames" | sed '/dev-desktop.*nginx/!d;s/nginx/php/'`
staging=`echo "$cnames" | sed '/staging.*nginx/!d;s/nginx/php/'`

echo "Uploading FAQs to the staging, dev-desktop, dev-mobile databases"

ssh staging2 << VERYVERYLONGEOF
    set -eux pipefail
    echo "Uploading FAQs to the dev-desktop databases"
    docker exec -i "$ddesktop_db" mysql --database awardwallet < /tmp/faq.sql
    echo "Uploading FAQs to the staging databases"
    docker exec -i "$staging_db" mysql --database "$stbase" < /tmp/faq.sql
VERYVERYLONGEOF


PREF='/var/lib/jenkins/workspace/Frontend/deploy-frontend'
TRANS='translations'

cd "$PREF"
git pull
docker-compose run -v "$PREF/$TRANS:/www/awardwallet/$TRANS:rw" --rm php app/console -vv --env=prod --no-debug translation:extract --config=database
docker-compose run -v "$PREF/$TRANS:/www/awardwallet/$TRANS:rw" --rm php app/console -vv --env=prod --no-debug translation:update-from-db

cd "$TRANS"
git add *.xliff
git commit -m 'FAQ Translations'
git push


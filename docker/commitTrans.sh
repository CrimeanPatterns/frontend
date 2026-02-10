#!/usr/bin/env bash
set -euxo pipefail

TRANS='translations'

cd `dirname "$0"`/..
PREF="$(pwd)"

APATH=`ssh staging2 /opt/serverscripts/staging/getactive staging 2>/dev/null`
PPATH=`ssh staging2 /opt/serverscripts/staging/getpassive staging 2>/dev/null`

ssh staging2 <<EOF
set -eux
cd "/www/staging/$APATH"
docker-compose run --rm php app/console translation:fix-english-translations -vv
EOF

git pull
rsync -zavP "staging2:/www/staging/$APATH/$TRANS/*" "$TRANS/"
git add "$TRANS"
git --no-pager diff --staged

export GIT_SSH_COMMAND="ssh -i ~/.ssh/commit-trans-github-key"
if [ "$commit" == "true" ]; then
    echo "making commit..."
    git commit -m "translations from dev" && git push origin master || echo 'Commit failed. There is probably nothing to commit.'
else
    echo "no commit, just diff"
    git reset HEAD --hard
    git clean -df
    git pull
fi

ssh staging2 <<EOF
set -euxo pipefail
cd "/www/staging/$APATH"
git reset HEAD --hard
git clean -fd
git pull
cd "/www/staging/$PPATH"
git reset HEAD --hard
git clean -fd
git pull
EOF


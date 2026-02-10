#!/usr/bin/env bash

set -euxo pipefail
RSYNC_OPTIONS='-azO'

date
rsync --verbose --delete "$RSYNC_OPTIONS" --size-only --ignore-times --exclude=temp aw1.awardwallet.com:/www/awardwallet/web/images/uploaded/ /backups/uploaded
date
sudo -u www-data rsync  --verbose "$RSYNC_OPTIONS" --size-only --ignore-times --exclude=temp /backups/uploaded/ /mnt/efs/uploaded2/
date

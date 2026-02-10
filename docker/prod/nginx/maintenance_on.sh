#!/bin/bash -x

set -euxo pipefail

cd /etc/nginx/conf.d/
cp maintenance.conf.template default.conf
nginx -s reload
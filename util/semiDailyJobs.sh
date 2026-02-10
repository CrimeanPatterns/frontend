#!/usr/bin/env bash
set -uxo pipefail

err=''
php util/check/analyzeBalances.php || err="$err\nanalyzeBalances"

[ -n "$err" ] && echo -e "Something is failed:\n$err" && exit 1
exit 0


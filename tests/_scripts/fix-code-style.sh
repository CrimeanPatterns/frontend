#!/usr/bin/env bash
set -euxo pipefail

CHANGED_FILES=$(git diff --name-only --diff-filter=ACMRTUXB "origin/master...HEAD")
if [[ "${CHANGED_FILES[@]}" != "" ]]; then
    ./vendor/bin/php-cs-fixer fix --ansi --config=.php-cs-fixer.dist.php --path-mode=override --verbose '--' ${CHANGED_FILES[@]}
fi
#!/usr/bin/env bash

set +euxo pipefail

for patch in docker/prod/patches/*.patch
do
  patch -p 1 < $patch
done
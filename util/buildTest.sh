#!/bin/bash
git pull || exit 1
git submodule foreach "git pull" || exit 1
rm -Rf app/cache/*
./install-vendors.sh --prefer-source || exit 1
grunt --gruntfile desktopGrunt.js || exit 1
grunt --no-color build:mobile || exit 1

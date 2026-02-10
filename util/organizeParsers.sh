#!/bin/bash
echo "" > "organizeLog.log"
provider="*"
lastprovider="*"
run=1
while [[ $# > 0 ]]
do
	key="$1"
	case ${key} in
	-p)
		shift
		if [ -z "$1" ] ; then
			echo "Missing provider code"
			exit 1
		fi
		provider="$1"
		run=0
	;;
	-pp)
		shift
		if [ -z "$1" ] ; then
			echo "Missing provider code"
			exit 1
		fi
		if [ "$provider" == "*" ] ; then
			echo "Missing starting provider"
			exit 1
		fi
		lastprovider="$1"
	;;
	esac
	shift
done
COUNT=`ls -1 web/engine | wc -l`
if [ "$COUNT" -lt 800 ]; then
	echo 'Run from awardwallet home dir'
	exit 1
fi
SVN=`svn info . | grep 'Revision'`
if [ -z "$SVN" ]; then
	echo 'SVN failed'
	exit 1
fi
echo "SVN ok"
shopt -s nullglob
idx=0
COUNT=$((COUNT + 0))
for ROOT in web/engine/*
do
	code="$(basename "$ROOT")"
	if [ "$code" == "$provider" ] ; then
		run=1
	fi
	if [ "$run" -eq 0 ] ; then
		continue
	fi
	for path in "$ROOT"/email*Checker.php
	do
		file="$(basename "$path")"
		name=${file:5:${#file}-16}
		if [ $(expr "$name" : "Credentials") -eq 0 ] ; then
		# email parser
			newName="$name"
			if [ -z "$newName" ] ; then
				newName='Itinerary1'
			fi
			if [ $(expr "$newName" : '[0-9]') -ne 0 ] ; then
				newName="Itinerary$newName"
			fi
			if [ ! -d "$ROOT/Email" ] ; then
				mkdir "$ROOT/Email"
				svn add "$ROOT/Email" > /dev/null
			fi
			newPath="$ROOT/Email/$newName.php"
			namespace="namespace AwardWallet\\\\Engine\\\\$code\\\\Email;"
		else
		# credentials
			newName=${name:11}
			if [ -z "$newName" ] ; then
				newName='Credentials'
			fi
			if [ $(expr "$newName" : '[0-9]') -ne 0 ] ; then
				newName="Credentials$newName"
			fi
			if [ ! -d "$ROOT/Credentials" ] ; then
				mkdir "$ROOT/Credentials"
				svn add "$ROOT/Credentials" > /dev/null
			fi
			newPath="$ROOT/Credentials/$newName.php"
			namespace="namespace AwardWallet\\\\Engine\\\\$code\\\\Credentials;"
		fi
		#echo "moving $path -> $newPath"
		echo -n "."
		svn move "$path" "$newPath" > /dev/null
		#mv "$path" "$newPath"
		line="$(head -n 1 "$newPath")"
		if [ "$(expr "$line" : '^<?php')" -eq 0 ] ; then
			echo "$newPath php tag missing"
		else
			gsed -i "2i\ \n$namespace\n" "$newPath"
			gsed -i "s/class [a-zA-Z0-9]* extends TAccountChecker/class $newName extends \\\\TAccountChecker/
			s/\\\\*PlancakeEmailParser/\\\\PlancakeEmailParser/
			s/new \\\\*DOM/new \\\\DOM/
			s/\\\\*DOMNode/\\\\DOMNode/
			s/\\\\*DOMXPath/\\\\DOMXPath/
			s/\\\\*TBaseBrowser/\\\\TBaseBrowser/
			s/new \\\\*CruiseSegmentsConverter/new \\\\CruiseSegmentsConverter/
			s/new \\\\*TAccountChecker/new \\\\TAccountChecker/
			s/new \\\\*DateTime/new \\\\DateTime/
			s/\\\\*DateTime::/\\\\DateTime::/
			s/\\\\*WebDriverBy::/\\\\WebDriverBy::/
			s/new \\\\*Exception/new \\\\Exception/
			s/new \\\\*PDF/new \\\\PDF/
			s/new \\\\*stdClass/new \\\\stdClass/
			s/\\\\*PDF::/\\\\PDF::/
			s/\\\\*Cache::/\\\\Cache::/
			s/use \\\\*SeleniumCheckerHelper/use \\\\SeleniumCheckerHelper/
			s/use \\\\*PointsDotComGwtOperations/use \\\\PointsDotComGwtOperations/
			s/\\\\*CheckException/\\\\CheckException/
			s/\\\\*CaptchaException/\\\\CaptchaException/"  "$newPath"
			syntax="$(php -l "$newPath" | grep 'No syntax errors detected' | wc -l)"
			if [ "$syntax" -eq 0 ] ; then
				tput setaf 1
				echo
				echo "Syntax errors detected in $newPath"
				tput sgr0
				echo "Syntax errors detected in $newPath" >> "organizeLog.log"
			fi
		fi
	done
	ms=(register.php transfer.php purchase.php)
	for file in ${ms[*]}
	do
		if [ -f "$ROOT/$file" ] ; then
			if [ ! -d "$ROOT/Transfer" ] ; then
				mkdir "$ROOT/Transfer"
				svn add "$ROOT/Transfer" > /dev/null
			fi
			namespace="namespace AwardWallet\\\\Engine\\\\$code\\\\Transfer;"
			case "$file" in
			"register.php")
				name="$code""AccountRegistrator"
				newName="Register"
				newPath="$ROOT/Transfer/Register.php"
				;;
			"purchase.php")
				name="$code""RewardsPurchaser"
				newName="Purchase"
				newPath="$ROOT/Transfer/Purchase.php"
				;;
			"transfer.php")
				name="$code""RewardsTransferer"
				newName="Transfer"
				newPath="$ROOT/Transfer/Transfer.php"
				;;
			esac
			#echo "moving $ROOT/$file -> $newPath"
			echo "."
			#mv "$ROOT/$file" "$newPath"
			svn move "$ROOT/$file" "$newPath" > /dev/null
			gsed -i "2i\ \n$namespace\n" "$newPath"
			gsed -i "s/class [a-zA-Z0-9]* extends TAccountChecker/class $newName extends \\\\TAccountChecker/
			s/\\\\*PlancakeEmailParser/\\\\PlancakeEmailParser/
			s/new \\\\*DOM/new \\\\DOM/
			s/\\\\*DOMNode/\\\\DOMNode/
			s/\\\\*DOMXPath/\\\\DOMXPath/
			s/\\\\*TBaseBrowser/\\\\TBaseBrowser/
			s/new \\\\*CruiseSegmentsConverter/new \\\\CruiseSegmentsConverter/
			s/new \\\\*TAccountChecker/new \\\\TAccountChecker/
			s/new \\\\*DateTime/new \\\\DateTime/
			s/\\\\*DateTime::/\\\\DateTime::/
			s/\\\\*WebDriverBy::/\\\\WebDriverBy::/
			s/new \\\\*Exception/new \\\\Exception/
			s/new \\\\*PDF/new \\\\PDF/
			s/new \\\\*stdClass/new \\\\stdClass/
			s/\\\\*PDF::/\\\\PDF::/
			s/\\\\*Cache::/\\\\Cache::/
			s/use \\\\*SeleniumCheckerHelper/use \\\\SeleniumCheckerHelper/
			s/use \\\\*PointsDotComGwtOperations/use \\\\PointsDotComGwtOperations/
			s/\\\\*CheckException/\\\\CheckException/
			s/\\\\*CaptchaException/\\\\CaptchaException/"  "$newPath"
			syntax="$(php -l "$newPath" | grep 'No syntax errors detected' | wc -l)"
			if [ "$syntax" -eq 0 ] ; then
				tput setaf 1
				echo
				echo "Syntax errors detected in $newPath"
				tput sgr0
				echo "Syntax errors detected in $newPath" >> "organizeLog.log"
			fi
		fi
	done
	idx=$((idx + 1))
	mod=$((idx % 20))
	if [ "$mod" -eq 0 ] ; then
		tput setaf 5
		echo
		echo "done $idx out of $COUNT providers"
		tput sgr0
	fi
	if [ "$code" == "$lastprovider" ] ; then
		break
	fi
done
echo "Old parsers remaining:"
for p in web/engine/${provider}/email*Checker.php
do
	echo "$p"
done
echo "Done"
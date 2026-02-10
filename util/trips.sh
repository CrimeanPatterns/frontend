#!/bin/bash
set -eo pipefail
cd ~/vars/mysql

[ -n "$1" ] && UserID="$1" || UserID='7'

fe='mysql --defaults-extra-file=frontend.cnf -B -N'
arc='mysql --defaults-extra-file=awarchive.cnf -B -N'

tssql='SELECT DISTINCT DepCode,ArrCode,DepDate,ArrDate FROM Trip 
JOIN TripSegment ON Trip.TripID = TripSegment.TripID
WHERE Trip.UserID = '"$UserID"' AND Trip.UserAgentID is null AND Trip.Hidden = 0 AND Trip.Cancelled = 0 AND DepCode != "" AND ArrCode != ""
ORDER BY DepDate;'
acodes='select AirCode, CountryCode, CountryName, CityName from AirCode where AirCode in'

# Getting all trip segments from two databases
tsres=`echo "$tssql" | $arc && echo "$tssql" | $fe`
# Getting all of unique aircodes from trip segments
ccodes=`echo "$tsres" | awk '{ print $1"\n"$2 }' | sort -u`
# Getting all country codes for given AirCodes
ccodesres=`echo "$ccodes" | xargs | sed 's/ /","/g;s/^/'"$acodes"' ("/;s/$/")/' | $arc`

IFS=$'\n'
for tr in $tsres; do
    DepCountry=`echo "$ccodesres" | sed '/^'"$(echo "$tr" | awk '{ print $1 }')"'[ \t]*/!d'`
    ArrCountry=`echo "$ccodesres" | sed '/^'"$(echo "$tr" | awk '{ print $2 }')"'[ \t]*/!d'`
    DepCCode=`echo "$DepCountry" | awk '{ print $2 }'`
    ArrCCode=`echo "$ArrCountry" | awk '{ print $2 }'`
    if [ "$DepCCode" != "$ArrCCode" ]; then
        DepCountryName=`echo "$DepCountry" | awk -F $'\t' '{ print $3 }'`
        ArrCountryName=`echo "$ArrCountry" | awk -F $'\t' '{ print $3 }'`
        DepCityName=`echo "$DepCountry" | awk -F $'\t' '{ print $4 }'`
        ArrCityName=`echo "$ArrCountry" | awk -F $'\t' '{ print $4 }'`
        DDep=`echo "$tr" | awk '{ print $3 }'`
        echo "$DDep: $DepCountryName ($DepCityName) -> $ArrCountryName ($ArrCityName)"
    fi
done


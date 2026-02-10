<?php

$schema = "usersReports";
require "../../start.php";
drawHeader("OneWorld Users");

print "<h2>OneWorld Users</h2><br>";

$allianceId = 3;
/* print "<p>Alliance Name: <b>".Lookup('Alliance', 'AllianceID', 'Name', $allianceId)."</b></p>";
print "<p>Airlines with itineraries: <b>".implode(', ', SQLToSimpleArray("SELECT Code FROM Provider WHERE AllianceID = $allianceId AND CanCheck = 1 AND CanCheckItinerary = 1", 'Code'))."</b></p>";

print "<p>Countries in South America with airports: <b>".implode(', ', array_unique(SQLToSimpleArray("
		SELECT DISTINCT CountryName FROM AirCode WHERE
	    -60 <= Lat AND Lat <= 13 AND -110 <= Lng AND Lng <= -26
	", 'CountryName')))."</b></p>"; */

$q = new TQuery("select count(distinct(UserID)) as `count` from TripSegment join Trip on Trip.TripID = TripSegment.TripID where (ArrCode in (select AirCode from AirCode WHERE -60 <= Lat AND Lat <= 13 AND -110 <= Lng AND Lng <= -26) and DepDate > now()) and Cancelled = 0 and ProviderID in (select ProviderID from Provider where AllianceID = $allianceId)");
print $q->Fields['count']." users are going to fly to South America using OneWorld airlines";

print "<br /><br />";
print "<a href = \"javascript:history.back()\">Back</a>"; 
drawFooter();

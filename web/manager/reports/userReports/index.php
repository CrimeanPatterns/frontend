<?php

$schema = "usersReports";
require "../../start.php";
drawHeader("User reports");

$arActions = array(
	'activeFlyUsers' => 'Active Fly Users (TLV)',
	'cruiseUsers' => 'Cruise Users',
	'caliUsers' => 'California Users',
    'deltaStarwood' => 'Delta & Starwood Users',
	'oneworldUsers' => 'OneWorld Users',
    'usAirways' => 'US Airways Users',
);

print "<ul>";
foreach ($arActions as $link => $label)
	print "<li><a href='$link.php'>$label</a></li>";
print "</ul>";


print "<br>";
drawFooter();

#!/usr/bin/php
<?php
require __DIR__ . "/../../web/kernel/public.php";

chdir(__DIR__);

$options = getopt("l:h");
if(isset($options['h'])){
	fputs(STDERR, "
Usage: php updateAirlines.php [options]
Options:
    -h <help>
    -l <time limit>\n
");
	exit(1);
}

#time limit
$limit = 3600*20;
if(isset($options['l']))
    $limit = intval($options['l']);

if($limit > 0)
    set_time_limit($limit);

global $Connection;
sLog('Update Airlines started');
$updateCounter = $insertCounter = $totalCounter = $deleteCounter = 0;
$http = new HttpBrowser("none", new CurlDriver());
$http->LogMode = 'nothing';
#get all countries
$http->GetURL("http://www.flightradar24.com/data/airplanes/");
$rows = $http->XPath->query("//ul[@id='airlineList']/li");
$data = [];
/* @var \DOMNode $row */
foreach ($rows as $row) {
	foreach(['data-name', 'data-iata', 'data-icao'] as $attr)
		if (null === $row->attributes->getNamedItem($attr))
			continue;
	$name = $row->attributes->getNamedItem('data-name')->nodeValue;
	$code = $row->attributes->getNamedItem('data-iata')->nodeValue;
	if ('xx' == $code)
		$code = "";
	if (!empty($name))
		$data[$name] = [
			"Name" => $name,
			"Code" => $code,
			"ICAO" => $row->attributes->getNamedItem('data-icao')->nodeValue,
		];
}
sLog(sprintf('parsed %d airlines from website', count($data)));
if (count($data) < 700) {
	sLog('Got less than 700 airlines from http://www.flightradar24.com/data/airplanes/, exiting');
	mailTo(EMAIL_TEST_ADDRESS, "updateAirlines.php: Can't find airlines", "http://www.flightradar24.com/data/airplanes/ - parsed ".count($data)." airlines", "Content-Type: text/html; charset=utf-8;");
	exit();
}
$db = SQLToArray('select AirlineID, Code, Name, ICAO from Airline', 'Name', 'AirlineID', true);
$fix = [];
foreach ($db as $row) {
	if (isset($data[$row['Name']])) {
		$airline = $data[$row['Name']];
		if (0 === strcasecmp($airline['Code'], $row['Code']) && 0 === strcasecmp($airline['ICAO'], $row['ICAO'])) {
			//sLog(sprintf('[%d %s %s %s] OK', $row['AirlineID'], $row['Name'], $row['Code'], $row['ICAO']));
		}
		else {
			sLog(sprintf('updating [%d %s %s %s] -> [%s %s %s]', $row['AirlineID'], $row['Name'], $row['Code'], $row['ICAO'], $airline['Name'], $airline['Code'], $airline['ICAO']));
			$update = inQuotes(array_merge($airline, ['LastUpdateDate' => 'NOW()']));
			$Connection->Execute(UpdateSQL('Airline', ['AirlineID' => $row['AirlineID']], $update));
			$updateCounter++;
		}
		unset($data[$row['Name']]);
		$totalCounter++;
	}
	else {
		$fix[$row['Name']] = $row;
	}
}
sLog(sprintf('got %d records and %s airlines left after names check', count($fix), count($data)));
$db = $fix;
foreach($db as $row) {
	if (!empty($row['Code']) && ($airline = findByCode($data, $row['Code']))) {
		sLog(sprintf('updating [%d %s %s %s] -> [%s %s %s]', $row['AirlineID'], $row['Name'], $row['Code'], $row['ICAO'], $airline['Name'], $airline['Code'], $airline['ICAO']));
		$update = inQuotes(array_merge($airline, ['LastUpdateDate' => 'NOW()']));
		$Connection->Execute(UpdateSQL('Airline', ['AirlineID' => $row['AirlineID']], $update));
		unset($data[$airline['Name']]);
		$updateCounter++;
		$totalCounter++;
	}
	else {
		sLog(sprintf('deleting [%d %s %s %s]', $row['AirlineID'], $row['Name'], $row['Code'], $row['ICAO']));
		$Connection->Execute(DeleteSQL('Airline', ['AirlineID' => $row['AirlineID']]));
		$deleteCounter++;
	}
}
sLog(sprintf('got %s airlines left after code check', count($data)));
foreach($data as $airline) {
	$insert = inQuotes(array_merge($airline, ['LastUpdateDate' => 'NOW()']));
	$Connection->Execute(InsertSQL('Airline', $insert));
	$insertCounter++;
	$totalCounter++;
}

sLog('Total processed: ' . $totalCounter);
sLog('Updated: ' . $updateCounter);
sLog('Inserted: ' . $insertCounter);
sLog('Deleted: ' . $deleteCounter);
if ($totalCounter < 800)
	mailTo(EMAIL_TEST_ADDRESS, "Total Airlines < 800", "total airlines = $totalCounter, /util/update/updateAirlines.php", "Content-Type: text/html; charset=utf-8;");

function inQuotes($data){
    $notQuotes = array('LastUpdateDate');
    foreach($data as $k => $row) {
        if(!in_array($k, $notQuotes)){
            if(isset($row))
                $data[$k] = mysql_quote($row);
            else
                unset($data[$k]);
        }
    }
    return $data;
}

function sLog($s) {
	echo "[".date("Y-m-d h:i:s")."] $s\n";
}

function findByCode($data, $code) {
	foreach($data as $row) {
		if ($row['Code'] == $code)
			return $row;
	}
	return null;
}

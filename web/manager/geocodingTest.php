<?php

$schema = "geocodingTest";
require "start.php";
drawHeader("Geocoding API Test");

require_once("$sPath/lib/geoFunctions.php");

$_GET['DebugGeoTag'] = true;

$arActions = array(
	'address' => 'Input address by yourself',
	'hotels' => 'Test hotel addresses',
	'report' => 'Statistic report',
);

print "<h2>Menu</h2><ul>";
foreach ($arActions as $link => $label)
	print "<li><a href='?action=$link'>$label</a></li>";
print "</ul>";
$value = 0;
$color = GoogleGeoTagLimitOk($value) ? 'green' : 'red';
print "<p>Requests (last 24 hours): <span style='color: $color;'>$value</span>/2500</p>";

$page = max(0, intval(ArrayVal($_GET, 'page', 0)));
$perpage = 10;
$offset = $page * $perpage;

$action = ArrayVal($_GET, 'action', null);

$paginator = "<p><a href='?action=$action&page=".max(0, $page-1)."'>&larr;</a> Page $page <a href='?action=$action&page=".($page+1)."'>&rarr;</a></p>";

if (in_array($action, array_keys($arActions)))
	call_user_func($action);

function address() {
	global $action;

	$address = '';
	$place = '';
	if (isset($_GET['address'])) {
		$address = CleanXMLValue($_GET['address']);
		$place = CleanXMLValue($_GET['place']);
		print("<h2>Answer</h2>Address: '$address', place: '$place'<hr>");
		$result = FindGeoTag($address, $place);
		print("<hr>Result:<pre style='margin: 0;'>".trim(var_export($result, true))."</pre>");
	}
	$checked = isset($_GET['ResetGeoTag']) ? ' checked="checked"' : '';
	print "<h2>Form</h2><form action='?'>
		<input type='hidden' name='action' value='".htmlspecialchars($action)."'>
		Address: <input type='text' name='address' size='50' value='".htmlspecialchars($address)."'><br/>
		Place: <input type='text' name='place' size='50' value='".htmlspecialchars($place)."'><br/>
		<input type='submit' value='Find'><br>
		<input type='checkbox' name='ResetGeoTag'$checked> <label for='ResetGeoTag'>ResetGeoTag</label>
		</form>";
}

function hotels() {
	global $paginator, $perpage, $offset;

	if (ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
		print "Only for test server!";
		return;
	}
	$_GET['DebugGeoTag'] = null;

	$legend = "<h2>Legend</h2><p><div style='background-color: #d3d3d3;'>Debug info</div>
				<div style='background-color: #e0ffff;'>Input address from table</div>
				<div style='background-color: white;'>Formatted address by Geocoding API (string)</div>
				<div style='background-color: #90ee90;'>Combined address from parts by Geocoding API (array) as 'AddressLine | City | State | Country | PostalCode'</div></p>";

	print "$legend$paginator<table border='1'>";
	$q = new TQuery("SELECT Address FROM Reservation GROUP BY Address ORDER BY ReservationID DESC LIMIT $perpage OFFSET $offset");
	while (!$q->EOF) {
		$q->Fields['Address'] = CleanXMLValue($q->Fields['Address']);
		if (!empty($q->Fields['Address'])) {
			//print "<tr bgcolor='#d3d3d3'><td>";
			$lat = $lng = $address = null;
			GoogleGeoTag($q->Fields['Address'], $lat, $lng, $address);
			//print "</td></tr>";

			foreach (array('Formatted', 'AddressLine', 'City', 'State', 'Country', 'PostalCode') as $key)
				$address[$key] = ArrayVal($address, $key, '<i style="color: #8b0000;">null</i>');
			$combine = "{$address['AddressLine']} | {$address['City']} | {$address['State']} | {$address['Country']} | {$address['PostalCode']}";

			//print "<tr bgcolor='#d3d3d3'><td>";
			$allNumbers = false;
			if (preg_match_all('/(?<number>\d+)/', $q->Fields['Address'], $matches)) {
				$exists = 0;
				foreach ($matches['number'] as $number) {
					if (strpos($combine, $number) !== false) {
						$exists += 1;
					}
				}
				if ($exists == count($matches['number']))
					$allNumbers = true;
			}
			//print "</td></tr>";

			$color = $allNumbers ? '#90ee90' : '#f08080';

			print "<tr bgcolor='#e0ffff'><td>{$q->Fields['Address']}</td></tr>";
			print "<tr><td>{$address['Formatted']}</td></tr>";
			print "<tr bgcolor='{$color}'><td>{$combine}</td></tr>";
			print "<tr bgcolor='black'><td></td></tr>";
		}
		$q->Next();
	}
	print "</table>$paginator";
}

function report() {
	global $Connection;

	$stat = array();

	$q = new TQuery("SELECT DATE(UpdateDate) AS Date, Count(*) as Count FROM GeoTag GROUP BY DATE(UpdateDate) ORDER BY Date ASC");
	while (!$q->EOF) {
		$stat[$q->Fields['Date']] = $q->Fields['Count'];

		$q->Next();
	}
	ksort($stat);

	print "<h2>Stats</h2><table cellpadding='0' cellspacing='1'>";
	foreach ($stat as $date => $count) {
		$px = round(min($count, 2500) / 2500 * 1000);
		if ($count >= 2500)
			$color = '#ee9090';
		elseif ($count >= 2500*0.75)
			$color = '#eeee90';
		else
			$color = '#90ee90';
		print "<tr><td>$date</td>
			<td>&mdash;</td>
			<td style='width: 1000px; background: #87ceeb;'>
				<div style='background: $color; width: {$px}px; display: inline-block;'>$count</div>
			</td></tr>";
	}
	print "</table>";
}

print "<br>";
drawFooter();

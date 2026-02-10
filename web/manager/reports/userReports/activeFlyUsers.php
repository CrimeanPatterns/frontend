<?php

$schema = "activeFlyUsers";
require "../../start.php";
drawHeader("Active Fly Users");

print "<h2>Active Fly Users</h2><br>";

$disabled = ' disabled="disabled"';
$airname = $aircode = preg_replace('/[^A-Z0-9]+/', '', substr(strtoupper(CleanXMLValue(ArrayVal($_POST, 'aircode', 'TLV'))), 0, 3));
$aircodecheck = '&nbsp;';
$timespan = intval(ArrayVal($_POST, 'timespan', 18));
$timespancheck = '&nbsp;';
$exceptLP = intval(ArrayVal($_POST, 'expect', 66));
$exceptLPcheck = '&nbsp;';

if (isset($_POST['check']) || isset($_POST['submit'])) {
	$q = new TQuery("SELECT CityName, CountryName FROM AirCode WHERE AirCode = '".addslashes($aircode)."'");
	if (!$q->EOF) {
		$aircodecheck = $q->Fields['CityName'] . ', ' . $q->Fields['CountryName'];
		$airname = $q->Fields['CityName'];
	} else {
		$aircodecheck = 'Not found';
	}
	$q = new TQuery("SELECT DATE(NOW() - INTERVAL $timespan MONTH) AS Date");
	if (!$q->EOF) {
		$timespancheck = strtotime($q->Fields['Date']);
	} else {
		$timespancheck = strtotime("- $timespan months");
	}
	$timespancheck = 'From '.date(DATE_LONG_FORMAT, $timespancheck);

	$exceptLPcheck = Lookup('Provider', 'ProviderID', 'DisplayName', $exceptLP);

	$disabled = '';
}
$options = array("-1" => "No one") + SQLToArray("SELECT ProviderID, `Code` FROM Provider ORDER BY Code", "ProviderID", "Code");
$optionsHtml ='';
foreach ($options as $key => $value) {
	$selected = $exceptLP == $key ? ' selected="selected"' : '';
	$optionsHtml .= "<option value='$key' $selected>$value</option>";
}

print <<<HTML
<form action="" method="POST">
<table border="0" cellpadding="3">
<tr>
<td>Destination air code:</td>
<td><input type="text" name="aircode" maxlength="3" size="3" value="$aircode"></td>
<td>$aircodecheck</td>
</tr>
<tr>
<td>Time span:</td>
<td>last <input type="text" name="timespan" maxlength="3" size="3" value="$timespan"> months</td>
<td>$timespancheck</td>
</tr>
<tr>
<td>Except:</td>
<td><select name="expect">$optionsHtml</select></td>
<td>$exceptLPcheck</td>
</tr>
<tr>
<td colspan="3">
<input type="submit" name="check" value="check">
<input type="submit" name="submit" value="submit" $disabled>
</td>
</tr>
</table>
</form>
HTML;

if (isset($_POST['submit'])) {
	print "<h2>Report</h2>";

	$airlines = array();
	$users = array();

	$startTimer = microtime(true);
	$q = new TQuery("
		SELECT DISTINCT t.UserID, ts.AirlineName, p.DisplayName
		FROM Trip AS t
			JOIN TripSegment AS ts ON t.TripID = ts.TripID
			JOIN Provider AS p ON p.ProviderID = t.ProviderID
		WHERE t.Cancelled = 0
		AND ts.ArrCode = '$aircode'
		AND DATE(ts.DepDate) >= DATE(NOW() - INTERVAL $timespan MONTH)
		AND ts.DepDate <= NOW()
	");
	print "<pre>{$q->SQL}</pre>";
	while (!$q->EOF) {
		$users[] = $q->Fields['UserID'];
		$airlines[$q->Fields['UserID']] = !empty($q->Fields['AirlineName']) ? "{$q->Fields['DisplayName']} [{$q->Fields['AirlineName']}]" : $q->Fields['DisplayName'];
		$q->Next();
	}
	$time = round((microtime(true) - $startTimer) * 1000, 2);
	print "<p>Found ".count($users)." users with trips to $aircode and unknown status in $time ms.</p>";

	$temp = array();

	$startTimer = microtime(true);
	$pkind = PROVIDER_KIND_AIRLINE;
	$q = new TQuery("
		SELECT a.UserID, ah.Description, p.DisplayName
		FROM AccountHistory AS ah
			JOIN Account AS a ON a.AccountID = ah.AccountID
			JOIN Provider AS p ON p.ProviderID = a.ProviderID
		WHERE DATE(ah.PostingDate) >= DATE(NOW() - INTERVAL $timespan MONTH)
		AND (ah.Description LIKE '%$aircode%' OR ah.Description LIKE '%$airname%')
		AND p.Kind = $pkind
		ORDER BY a.UserID
	");
	print "<pre>{$q->SQL}</pre>";
	$descriptions = array();
	$add = array();
	while (!$q->EOF) {
		if (!in_array($q->Fields['UserID'], $add) && (
			preg_match("/(to|-|\/|[A-Z]{3}).*$aircode/", $q->Fields['Description']) ||
			preg_match("/(to|-|\/).*$airname/i", $q->Fields['Description'])
			)
		) {
			$add[] += $q->Fields['UserID'];
		}

		if (!in_array($q->Fields['UserID'], $temp))
			$temp[] = $q->Fields['UserID'];

		if (empty($airlines[$q->Fields['UserID']]))
			$airlines[$q->Fields['UserID']] = $q->Fields['DisplayName'];

		isset($descriptions[$q->Fields['UserID']]) ? $descriptions[$q->Fields['UserID']] .= ' | '.$q->Fields['Description'] : $descriptions[$q->Fields['UserID']] = $q->Fields['Description'];
		$q->Next();
	}
	$add = count($add);
	if (count($temp) > 0 )
		$add = round($add / count($temp) * 100, 2);
	$time = round((microtime(true) - $startTimer) * 1000, 2);
	print "<p>Found ".count($temp)." users with history trips to $aircode and unknown status in $time ms (about $add% users has $aircode as destination).</p>";

	$startTimer = microtime(true);
	$users = array_merge($users, $temp);
	sort($users);
	$users = array_unique($users);
	$time = round((microtime(true) - $startTimer) * 1000, 2);
	print "<p>Found ".count($users)." unique users in total (sort and find unique in $time ms).</p>";

	$sum = 0;
	$sumByRank = array();
	$sumByLP = array();
	$sumChecked = 0;
	$sumTime = 0;
	print "<table style='border-collapse: collapse;' border=1><tr><th>UserID</th><th>Accounts</th><th>AccountID with max. status</th><th>LP status</th><th>Status</th><th>LP flight [Operated by]</th><th>Description</th></tr>";
	$kind = PROPERTY_KIND_STATUS;
	foreach ($users as $user) {
		$startTimer = microtime(true);
		$q = new TQuery("
			SELECT a.AccountID, a.ProviderID, ap.Val, a.UserID, p.DisplayName
			FROM Account AS a
				JOIN AccountProperty AS ap ON ap.AccountID = a.AccountId
				JOIN ProviderProperty AS pp ON pp.ProviderPropertyID = ap.ProviderPropertyID
				JOIN Provider AS p ON p.ProviderID = a.ProviderID
			WHERE a.UserId = $user
			AND ap.SubAccountID IS NULL
			AND pp.Kind = $kind
			AND p.Kind = $pkind
		");
		$accountId = '&nbsp;';
		$lp = '&nbsp;';
		$status = '&nbsp;';
		$accounts = 0;
		$maxrank = 0;
		$skip = false;
		while (!$q->EOF && !$skip) {
			$eliteLevelFields = getEliteLevelFields($q->Fields['ProviderID'], $q->Fields['Val']);
			if ($eliteLevelFields['Rank'] > 0)
				if ($exceptLP == $q->Fields['ProviderID']) {
					$accountId = $q->Fields['AccountID'];
					$lp = $q->Fields['DisplayName'];
					$status = '<i>Skip</i>';
					$skip = true;
				}
			if (!$skip && $eliteLevelFields['Rank'] > $maxrank) {
				$maxrank = $eliteLevelFields['Rank'];
				$accountId = $q->Fields['AccountID'];
				$lp = $q->Fields['DisplayName'];
				$status = $q->Fields['Val'];
			}
			$accounts += 1;
			$q->Next();
		}
		$sumChecked += $accounts;
		if (!$skip && $maxrank > 0) {
			$sum += 1;
			$sumByRank[$maxrank] = 1 + ArrayVal($sumByRank, $maxrank, 0);
			$sumByLP[$lp] = 1 + ArrayVal($sumByLP, $lp, 0);
		}
		$time = (microtime(true) - $startTimer) * 1000;
		$sumTime += $time;
		$time = round($time, 2);
		$desc = isset($descriptions[$q->Fields['UserID']]) ? $descriptions[$q->Fields['UserID']] : '&nbsp;';
		$desc = preg_replace("/($aircode|(?i)$airname)/", '<b>$0</b>', $desc);
		$air = isset($airlines[$q->Fields['UserID']]) ? $airlines[$q->Fields['UserID']] : '&nbsp;';
		print "<tr><td>$user</td><td>$accounts ($time ms)</td><td>$accountId</td><td>$lp</td><td>$status</td><td>$air</td><td>$desc</td></tr>";
	}
	$sumTime = round($sumTime, 2);
	print "<tr><th>Total:</th><th>$sumChecked ($sumTime ms)</th><th>$sum</th><th>&nbsp;</th><th>&nbsp;</th><th>&nbsp;</th><th>&nbsp;</th></tr>";
	print "</table>";

	$per = count($users) > 0 ? $sum / count($users) : 0;
	print "<p><b>Sum: $sum</b>/".count($users)." (".round($per * 100, 1)."%)</p>";

	ksort($sumByRank);
	ksort($sumByLP);
	print "<table style='border-collapse: collapse;' border=1>";
	print "<tr><th>Rank</th><th>Sum</th></tr>";
	foreach ($sumByRank as $rank => $value) {
		print "<tr><td>{$rank}</td><td align=right>{$value}</td></tr>";
	}
	print "</table>";
	print "<br>";
	print "<table style='border-collapse: collapse;' border=1>";
	print "<tr><th>LP</th><th>Sum</th></tr>";
	foreach ($sumByLP as $LP => $value) {
		print "<tr><td>{$LP}</td><td align=right>{$value}</td></tr>";
	}
	print "</table>";
}

print "<br>";
drawFooter();

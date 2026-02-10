#!/usr/bin/php
<?
require __DIR__."/../../web/kernel/public.php";
require_once __DIR__."/../../web/trips/common.php";

echo "this scripts will search for providers with many same balances
trying to detect situation when parser broken, and return same value
for all accounts\n";

$q = new TQuery("select ProviderID, DisplayName, CanCheckBalance, CanCheckExpiration, CanCheckItinerary
from Provider where State >= ".PROVIDER_ENABLED);
while(!$q->EOF){
	$warnings = array();
	if($q->Fields['CanCheckBalance'] == '1')
		analyzeBalances($q->Fields['ProviderID'], $warnings);
	analyzeExpiration($q->Fields['ProviderID'], $q->Fields['CanCheckExpiration'] == '1', $warnings);
	/*
	if($q->Fields['CanCheckItinerary'] == '1')
		analyzeItineraries($q->Fields['ProviderID'], $warnings);
	*/
	if(count($warnings) > 0)
		$warning = "'".addslashes(implode("\n", $warnings))."'";
	else
		$warning = "null";
	$Connection->Execute("update Provider set Warning = {$warning} where ProviderID = {$q->Fields['ProviderID']}");
	echo "{$q->Fields['ProviderID']} - {$q->Fields['DisplayName']}: {$warning}\n";
	$q->Next();
}

function analyzeBalances($providerId, &$warnings){
	$balances = SQLToArray("select TotalBalance, count(AccountID) as Cnt from Account
	where ProviderID = $providerId and UpdateDate > adddate(now(), -1)
	group by TotalBalance limit 100", "TotalBalance", "Cnt");
	$total = array_sum($balances);
	if($total > 50){
		foreach($balances as $balance => $count){
			$percent = round($count / $total * 100);
			if($percent >= 97){
				$warnings[] = "There are {$percent}% accounts with same {$balance} balance";
				break;
			}
		}
	}
}

function analyzeExpiration($providerId, $canCheckExpiration, &$warnings){
	global $Connection;
	$dates = SQLToSimpleArray("select distinct ExpirationDate from Account
	where ProviderID = $providerId and UpdateDate > adddate(now(), -1) and ExpirationAutoSet > ".EXPIRATION_UNKNOWN."
	limit 3", "ExpirationDate");
	foreach($dates as &$date)
		if($date == "")
			$date = "null";
		else
			$date = date("Y-m-d", $Connection->SQLToDateTime($date));
	if(count($dates) == 1 && $canCheckExpiration)
		$warnings[] = "Provider marked as CanCheckExpiration = YES, but there are only one expiration date: ".implode(", ", $dates);
	if(count($dates) > 1 && !$canCheckExpiration)
		$warnings[] = "Provider marked as CanCheckExpiration = NO, but there are multiple expiration dates: ".implode(", ", $dates);
}

function analyzeItineraries($providerId, &$warnings){
	$q = new TQuery(ItenarariesSQL(array("a.ProviderID = $providerId", "t.UpdateDate > adddate(now(), -1)"))." limit 1");
	if($q->EOF){
		$warnings[] = "No itineraries for last 24h";
	}
	else{
		$q = new TQuery(ItenarariesSQL(array(
			"a.ProviderID = $providerId",
			"a.UpdateDate > adddate(now(), interval -4 hour)",
			"[StartDate] > now()",
			"t.Parsed = 0",
		))." limit 1");
		if(!$q->EOF)
			$warnings[] = "Future itinerary not parsed, Account: ".$q->Fields['AccountID']
			.", ConfNo: ".$q->Fields['ConfirmationNumber']
			.", StartDate: ".$q->Fields['StartDate']
			.", ID: ".$q->Fields['Kind'].$q->Fields['ID']
			.", UserID: ".$q->Fields['UserID'];

	}
}

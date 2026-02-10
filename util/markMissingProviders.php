#!/usr/bin/php
<?
require "../web/kernel/public.php";

echo "marking missing providers\n";

$q = new TQuery("select ProviderID, Code from Provider order by Code");
while(!$q->EOF){
	$dir = "$sPath/engine/{$q->Fields['Code']}";
	if(!file_exists($dir)){
		echo $q->Fields['Code']."\n";
		$Connection->Execute("update Provider set State = ".PROVIDER_IN_DEVELOPMENT." where ProviderID = {$q->Fields['ProviderID']}");
	}
	$q->Next();
}

echo "marking questions\n";
$Connection->Execute("update Provider set
	Questions = 1
where
	ProviderID IN (SELECT DISTINCT ProviderID FROM Answer an INNER JOIN Account ac ON ac.AccountID = an.AccountID)");

echo "done\n";

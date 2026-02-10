#!/usr/bin/php
<?
require "../web/kernel/public.php";

echo "update keywords\n";

$q = new TQuery("
	SELECT * FROM Provider
");
$n = 0;
while(!$q->EOF){
	$keyWords = strtolower(trim(preg_replace(array("/[^a-zA-Z0-9\-]/ims", "/\s{2,}/"), array(" ", " "), $q->Fields['DisplayName'])));
	$keyWords .= ",".strtolower(trim(preg_replace(array("/[^a-zA-Z0-9\-]/ims", "/\s{2,}/"), array(" ", " "), $q->Fields['ProgramName'])));
	$Connection->Execute("update Provider set KeyWords = '".mysql_real_escape_string($keyWords)."' where ProviderID = ".$q->Fields['ProviderID']."");
	$n++;
	$q->Next();
}

echo "updated $n providers\n";

echo "done\n";

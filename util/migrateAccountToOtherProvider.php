<?php
require "../web/kernel/public.php";

if(count($argv) != 3)
	die("usage: addProvider.php <from:int> <to:int>\n");

/*$conn = new TMySQLConnection();
$conn->Open(array(
	"Host" => HOST,
	"Login" => LOGIN,
	"Password" => PASSWORD,
	"Database" => DATABASE
));*/

$from = intval($argv[1]);
$to = intval($argv[2]);

if($from === 0 || $to === 0) 
	die("error: use the numeric values for parameters \n");

$Connection->Execute("update Account set ProviderID={$to} where ProviderID={$from}");

?>

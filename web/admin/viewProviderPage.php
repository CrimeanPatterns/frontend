<?php
require "../kernel/public.php";
require_once "$sPath/schema/ProviderPage.php";

$nID = intval(ArrayVal($_GET, 'ID'));
$q = new TQuery("select * from ProviderPage where ProviderPageID = $nID");
if($q->EOF)
	DieTrace("ProviderPage not found, ID: $nID");
if(!isset($q->Fields[$_GET['Field']."HTML"]))
	DieTrace("Unknown field");
if(strpos($q->Fields[$_GET['Field']."HTML"], "%PDF") === 0){
	$q->Fields[$_GET['Field']."HTML"] = base64_decode(substr($q->Fields[$_GET['Field']."HTML"], 4));
	header("Content-Type: application/pdf");
}
echo $q->Fields[$_GET['Field']."HTML"];
?>
<?php
require "../kernel/public.php";

$sTitle = "Fix plan names (tags)";

require "$sPath/lib/admin/design/header.php";

ob_end_flush();
$q = new TQuery("select * from TravelPlan where Name like '%<%' or Name like '%>%'");
while(!$q->EOF){
	$sName = htmlspecialchars($q->Fields['Name']);
	echo "Fixing plan {$q->Fields['TravelPlanID']}: $sName<br>";
	$Connection->Execute("update TravelPlan set Name = '".addslashes($sName)."'
	where TravelPlanID = {$q->Fields['TravelPlanID']}");
	$q->Next();
}

require "$sPath/lib/admin/design/footer.php";
?>

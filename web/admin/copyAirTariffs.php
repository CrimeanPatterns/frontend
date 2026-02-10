<?php
require "../kernel/public.php";
require_once "../kernel/tariffFunctions.php";

$sTitle = "Copy air tariffs";

require "$sPath/lib/admin/design/header.php";

ob_end_flush();
$qGoal = new TQuery("select * from Goal order by SortIndex");
while(!$qGoal->EOF){
	echo "Goal: {$qGoal->Fields['Name']}<br>";
	$qProvider = new TQuery("select distinct ProviderID from AirTariff order by ProviderID");
	while(!$qProvider->EOF){
		$arValues = array();
		foreach(array("Economy", "Business", "First") as $sClass){
			$nGoal = FindMinimumPoints($qGoal->Fields["SrcAirport"], $qGoal->Fields["DstAirport"], time(), array($qProvider->Fields["ProviderID"]), $sClass, false);
			if(isset($nGoal))
				$arValues["Price".$sClass] = $nGoal;
			else
				$arValues["Price".$sClass] = "null";
		}
		$q = new TQuery("select * from GoalTarget where GoalID = {$qGoal->Fields["GoalID"]} and ProviderID = {$qProvider->Fields["ProviderID"]}");
		echo "Provider: {$qProvider->Fields['ProviderID']} ".implode(", ", $arValues)."<br>";
		if($q->EOF){
			$arValues["GoalID"] = $qGoal->Fields["GoalID"];
			$arValues["ProviderID"] = $qProvider->Fields["ProviderID"];
			$Connection->Execute(InsertSQL("GoalTarget", $arValues));
		}
		else
			$Connection->Execute(UpdateSQL("GoalTarget", $arValues));
		$qProvider->Next();
	}
	$qGoal->Next();
}

require "$sPath/lib/admin/design/footer.php";
?>

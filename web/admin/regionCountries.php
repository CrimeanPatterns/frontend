<?php
require "../kernel/public.php";
require_once "../kernel/tariffFunctions.php";

$regionID = intval(ArrayVal($_GET, 'RegionID', 0));
$q = new TQuery("select * from Region where RegionID = ".$regionID);
if($q->EOF)
	die("Region not found");

$sTitle = "Region countries for ".$q->Fields["Name"];

require("../lib/admin/design/header.php");

echo "'".implode("', '", RegionCountries($q->Fields['RegionID']))."'";

function RegionCountries($nRegionID){
	$arResult = array();
	$q = new TQuery("select * from RegionContent where ParentID = $nRegionID");
	while(!$q->EOF){
		if($q->Fields["RegionID"] != "")
			$arResult = array_merge($arResult, RegionCountries($q->Fields["RegionID"]));
		if($q->Fields["CountryCode"] != "")
			$arResult[] = $q->Fields["CountryCode"];
		$q->Next();
	}
	return $arResult;
}

require("../lib/admin/design/footer.php");

?>
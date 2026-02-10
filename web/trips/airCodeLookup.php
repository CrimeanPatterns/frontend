<?php
require "../kernel/public.php";
require_once "../account/common.php";

$sText = addslashes(ArrayVal($QS, 'Text'));
$sOrder = " ";
if(ArrayVal($_GET, "Field") == 'Code'){
	$sCondition = "AirCode like '$sText%'";
	$sValueField = "AirCode";
	$sInfoField = "Name";
	$sOrder .= "AirCode";
}
else{
	$sCondition = "AirName like '$sText%' or CityName like '$sText%' or AirCode like '$sText%'";
	$sValueField = "Name";
	$sInfoField = "AirCode";
	$sOrder .= "AirName";
}

if (isset($_SESSION['StateID'])) {
    if (!isset($_SESSION['StateCode']))
        $_SESSION['StateCode'] = Lookup("State", "StateID", "Code", $_SESSION['StateID']);
    if (isset($_SESSION['StateCode']))
        $sOrder = "IF(State = '{$_SESSION['StateCode']}', 1, 0) DESC, ".$sOrder;
}

if (isset($_SESSION['CountryID'])) {
    if (!isset($_SESSION['CountryCode']))
        $_SESSION['CountryCode'] = Lookup("Country", "CountryID", "Code", $_SESSION['CountryID']);
    if (isset($_SESSION['CountryCode']))
        $sOrder = "IF(CountryCode = '{$_SESSION['CountryCode']}', 1, 0) DESC, ".$sOrder;
}


$q = new TQuery("select AirCode, CountryCode, State, AirName, CityName, StateName, CountryName
from AirCode where $sCondition and AirCode <> '+++' order by $sOrder limit 50");
$arResult = array();
while(!$q->EOF){
    if(preg_match('/\w{3}/',$q->Fields['AirCode'])){
        BuildAirPortName($q->Fields);
        $arResult[] = array('id' => $q->Fields['AirCode'], 'value' => $q->Fields[$sValueField], 'info' => $q->Fields[$sInfoField]);
    }
	$q->Next();
}

$arResult = array("results" => $arResult);

header("Content-type: application/json");
echo json_encode($arResult);

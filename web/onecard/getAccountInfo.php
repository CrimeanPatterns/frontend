<?
require "../kernel/public.php";
require_once("common.php");
require_once("$sPath/account/common.php");
require_once("$sPath/kernel/TAccountInfo.php");

AuthorizeUser();

$countryId = intval(ArrayVal($_GET, 'Country'));
$regions = getCountryRegions($countryId);
$regionIds = array_keys($regions);

GetAgentFilters($_SESSION['UserID'], "All", $accountFilter, $couponFilter);
$sql = AccountsSQL($_SESSION['UserID'], $accountFilter, "0 = 1", "", "", "All");
$q = new TQuery($sql);
$result = array();
while(!$q->EOF){
	$acc = array();
	$accountId = $q->Fields['ID'];
//	$acc['Number'] = showAccountNumber($accountId);
	$acc['Level'] = showAccountLevel($accountId);
	$acc['Phone'] = TAccountInfo::getSupportPhone($q->Fields, $acc['Level'], $regionIds);
	$acc['UserAgentID'] = $q->Fields['UserAgentID'];
//	$acc['ProviderName'] = $q->Fields['ProviderName'];
//	$acc['AccountID'] = $accountId;
	$result[$accountId] = $acc;
	$q->Next();
}

header("Content-Type: application/json");
echo json_encode($result);

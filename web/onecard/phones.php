<?
require "../kernel/public.php";
require_once "common.php";
require_once("$sPath/account/common.php");

AuthorizeUser();

$accountId = intval(ArrayVal($_GET, 'AccountID'));
$countryId = intval(ArrayVal($_GET, 'Country'));

$q = getUserAccount($accountId);

if(!$q->EOF){
	$phones = array();
	$level = showAccountLevel($accountId);
	if($q->Fields['ProviderID'] != ""){
		if($level != "")
			$phones = getLevelPhones($level, $q->Fields['ProviderID']);
		$phones += getProviderPhones($q->Fields['ProviderID']);
	}
	foreach($phones as $fields){
		if($fields['Region'] != '')
			$title = "{$fields['Region']}: ";
		else
			$title = "";
		$title .= $fields['Phone'];
		$options = array();
		if($fields['Level'] != '')
			$options[] = $fields['Level'];
		if($fields['PhoneFor'] != PHONE_FOR_GENERAL)
			$options[] = $phoneForOptions[$fields['PhoneFor']];
		if(count($options) > 0)
			$title .= "  (".implode(", ", $options).")";
		echo "<div phone='{$fields['Phone']}' class='row'><span>{$title}</span></div>";
	}
	if(count($phones) == 0)
		echo "<div phone='' class='row'><span>There are no known phone numbers</span></div>"; /*checked*/
}
else{
	echo "None";
}

function getUserAccount($accountId){
	GetAgentFilters($_SESSION['UserID'], "All", $accountFilter, $couponFilter);
	$sql = AccountsSQL($_SESSION['UserID'], $accountFilter, "0 = 1", " and a.AccountID = $accountId", "", "All");
	return new TQuery($sql);
}

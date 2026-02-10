<?
require __DIR__ . "/../kernel/public.php";
require_once(__DIR__ . "/../account/common.php");
require_once(__DIR__ . "/../trips/common.php");
require_once(__DIR__ . "/../account/barcodeCommon.php");

define('AW_PLUS_ONLY', "AW Plus only");

if(ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_PRODUCTION){
	if(!isset($_SERVER["HTTPS"]) || ($_SERVER["HTTPS"] != "on"))
		die("use https");
}

$data = file_get_contents('php://input');

if(isset($_GET['Debug'])){
	$data = array("Version" => "10");
	foreach(array("Method", "PartnerLogin", "PartnerPassword", "Token") as $key)
		if(isset($_GET[$key]))
			$data[$key] = $_GET[$key];
	$data = json_encode($data);
}

$request = json_decode($data);

if(!is_object($request))
	die("invalid request");
if(!isset($request->Version))
	$request->Version = "8";

$response = array(
	"Version" => "8",
);
if($request->Version > 9)
	$response["Version"] = $request->Version;

if(!isset($request->Method))
	$response["Error"] = "Method required";

if(!isset($response['Error']) && isset($request->PartnerLogin)){
	if(isset($request->PartnerPassword)){
		$q = new TQuery("select 1 from OA2Client
		where Login = '".addslashes($request->PartnerLogin)."'
		and Pass = '".addslashes($request->PartnerPassword)."'");
		if(!$q->EOF){
			$allowedMethods = array("listAccounts");
			if(!in_array($request->Method, $allowedMethods)){
				$response['Error'] = "Partner {$request->PartnerLogin} is not allowed to use method: ".$request->Method;
			}
		}
		else
			$response['Error'] = 'Invalid partner credentials';
	}
	else
		$response['Error'] = 'Partner password not set';
}

if(!isset($response['Error'])){
	$response["Method"] = $request->Method;
	switch($request->Method){
		case "listAccounts":
			listAccounts($request, $response);
			break;
		default:
			$response["Error"] = "Unknown method";
	}
}


$response['CacheTag'] = md5(json_encode($response));
$response['CacheStatus'] = "Changed";
if(isset($request->CacheTag) && ($request->CacheTag == $response['CacheTag'])){
	$response['CacheStatus'] = 'NotModified';
	foreach(array_keys($response) as $key)
		if(!in_array($key, array("Version", "Method", "CacheTag", "CacheStatus")))
			unset($response[$key]);
}

if(isset($_GET['Debug'])){
	echo "<pre>".print_r($response, true)."</pre>";
	die();
}

header("Content-type: application/json");
echo json_encode($response);
exit();

function validKey($request, &$response, &$userFields){
	if(!isset($request->Token)){
		$response['Error'] = 'Missing token';
		return false;
	}
	$q = new TQuery("select u.*
	from OA2Token t
	join Usr u on t.UserID = u.UserID
	where Token = '".addslashes($request->Token)."'
	and t.Expires > now()");
	if(!$q->EOF){
		$request->Login = $q->Fields['Login'];
		$userFields = $q->Fields;
		return true;
	}
	else{
		$response['Error'] = 'BadToken';
		return false;
	}
}

function listAccounts($request, &$response, $findAccountId = null, $userFields = null){
	if(!isset($userFields) && !validKey($request, $response, $userFields))
		return;
	GetAgentFilters($userFields['UserID'], "All", $userAgentAccountFilter, $userAgentCouponFilter, false, false, false);
	$stateFilter = "p.State >= ".PROVIDER_ENABLED;
	if(ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG)
		$stateFilter .= " or p.State = ".PROVIDER_TEST;
	$q = new TQuery(AccountsSQL($userFields['UserID'], $userAgentAccountFilter, $userAgentCouponFilter, " and (p.State is null or $stateFilter) and p.ProviderID <> 1", "", "All")
		." order by case when UserAgentID is null and UserID = {$userFields['UserID']} then 1 else 2 end, UserName, DisplayName");
	GetAgentFilters($userFields['UserID'], "All", $userAgentAccountFilter, $userAgentCouponFilter, true);
	$canCheck = SQLToSimpleArray(AccountsSQL($userFields['UserID'], $userAgentAccountFilter, $userAgentCouponFilter, " and p.CanCheck = 1",  " and 0 = 1", "All"), "ID");
	GetAgentFilters($userFields['UserID'], "All", $userAgentAccountFilter, $userAgentCouponFilter, false, true);
	$canEdit = SQLToSimpleArray(AccountsSQL($userFields['UserID'], $userAgentAccountFilter, $userAgentCouponFilter, "",  " and 0 = 1", "All"), "ID");
	$result = array();
	$userIndex = -1;
	$userName = "";
	$awPlus = ($userFields['AccountLevel'] == ACCOUNT_LEVEL_AWPLUS);
	if($awPlus)
		$response["AWPlus"] = "1";
	else
		$response["AWPlus"] = "0";
	$datesShown = 0;
	$totalIndex = 0;
	while(!$q->EOF){
		foreach($q->Fields as $key => $value){
			if($value != "")
				$q->Fields[$key] = htmlspecialchars_decode($value);
		}
		if($q->Fields["UserName"] != $userName){
			$userIndex++;
			$userName = $q->Fields['UserName'];
			$userKey = sprintf("_%03d", $userIndex);
			$result[$userKey] = array(
				"UserName" => $q->Fields["UserName"],
				"Accounts" => array(),
			);
			$rowIndex = 0;
		}
		$accountKey = sprintf("_%03d", $rowIndex);
		$account = array(
			"DisplayName" => $q->Fields["DisplayName"],
			"Login" => $q->Fields["Login"],
			"TableName" => $q->Fields['TableName'],
			"ID" => $q->Fields['ID'],
			"AccountIndex" => $accountKey,
			"UserIndex" => $userKey,
			"TotalIndex" => sprintf("%03d", $totalIndex),
			"Kind" => $q->Fields['Kind'],
			"AutoLogin" => $q->Fields['MobileAutoLogin'],
		);

		if(isset($request->PartnerLogin)){
//			$account['Login2'] = $q->Fields['Login2'];
//			$account['Login3'] = $q->Fields['Login3'];
//			$account['Password'] = DecryptPassword($q->Fields['Pass']);
			$account['ProviderCode'] = $q->Fields['ProviderCode'];
		}

		//Hide login information
		$account = hideCreditCards($account, $account['Kind']);

		if($q->Fields['TableName'] == 'Account'){
			if(in_array($q->Fields['ID'], $canCheck) && ($q->Fields['ProviderID'] != '') && ($q->Fields['CheckInBrowser'] != CHECK_IN_CLIENT))
				$account["CanCheck"] = "1";
			else
				$account["CanCheck"] = "0";
			if(in_array($q->Fields['ID'], $canEdit) && ($q->Fields['ProviderID'] != '') && ($q->Fields['CheckInBrowser'] != CHECK_IN_CLIENT))
				$account["CanEdit"] = "1";
			else{
				$account["CanEdit"] = "0";
				$account["AutoLogin"] = "0";
			}
			$subAccounts = TAccountInfo::getSubaccountsInfo($q->Fields, true);
//			if(count($subAccounts) == 0){
				$account["History"] = SQLToArray(GetBalanceHistoryQuery($q->Fields['ID'], 7, null), "UpdateDate", "Balance");
				loadAccountInfo($request, $account, $q->Fields, $awPlus, $datesShown);
				$account['Groups'] = indexArray($account['Groups']);
				$result[$userKey]["Accounts"][$accountKey] = $account;
				$rowIndex++;
//			}
//			else{
				foreach($subAccounts as $subAccountInfo){
					$accountKey = sprintf("_%03d", $rowIndex);
					$subAccount = $account;
					$subAccount["DisplayName"] .= " - ".$subAccountInfo['DisplayName'];
					$q->Fields["DisplayName"] = $subAccountInfo["DisplayName"];
					$q->Fields["Balance"] = $subAccountInfo['Balance'];
					$q->Fields["SubAccountID"] = $subAccountInfo['SubAccountID'];
					$q->Fields["ExpirationDate"] = $subAccountInfo['ExpirationDate'];
					loadAccountInfo($request, $subAccount, $q->Fields, $awPlus, $datesShown);
					$subAccount['Groups'] = indexArray($subAccount['Groups']);
					$subAccount["History"] = SQLToArray(GetBalanceHistoryQuery($q->Fields['ID'], 7, $subAccountInfo['SubAccountID']), "UpdateDate", "Balance");
					$subAccount["SubAccountID"] = $subAccountInfo['SubAccountID'];
					$result[$userKey]["Accounts"][$accountKey] = $subAccount;
					$rowIndex++;
				}
//			}
			if(isset($findAccountId) && ($findAccountId == $q->Fields['ID'])){
				$response["UserIndex"] = $userKey;
				$response["AccountIndex"] = $accountKey;
				$response["Account"] = $result[$userKey]["Accounts"][$accountKey];
			}
		}
		if($q->Fields['TableName'] == 'Coupon'){
			loadCouponInfo($account, $q->Fields);
			$account['Groups'] = indexArray($account['Groups']);
			$result[$userKey]["Accounts"][$accountKey] = $account;
			$rowIndex++;
		}
		$totalIndex++;
		$q->Next();
	}
	$response["Accounts"] = $result;
}

function indexArray($groups){
	$result = array();
	$groupIndex = 0;
	foreach($groups as $groupName => $groupValue){
		$groupKey = sprintf("_%03d", $groupIndex);
		$result[$groupKey] = array(
			"Name" => $groupName,
		);
		if(is_array($groupValue))
			$result[$groupKey]["Value"] = indexArray($groupValue);
		else
			$result[$groupKey]["Value"] = $groupValue;
		$groupIndex++;
	}
	return $result;
}

function loadCouponInfo(&$account, $arFields){
	global $Connection;
	// Account Info
	$groups = array();
	$groups[$arFields['DisplayName']] = array(
		"Owner" => $arFields['UserName'],
		"Description" => $arFields['Description'],
		"Value" => $arFields['Value'],
	);
	if($arFields['ExpirationDate'] != ''){
		$d = $Connection->SQLToDateTime($arFields["ExpirationDate"]);
		$sExpirationDate = date( DATE_FORMAT, $d );
		$groups[$arFields['DisplayName']]['Expiration Date'] = $sExpirationDate;
	}
	$account['Groups'] = $groups;
}

function loadAccountInfo($request, &$account, $arFields, $awPlus = null, &$datesShown = null){
	global $Connection;
	
	//Hide credit cards info
	$arFields = hideCreditCards($arFields, $arFields['Kind']);

	// Account Info
	$groups = array();
	$groups[$arFields['DisplayName']] = array(
		"Owner" => $arFields['UserName'],
		"Balance" => formatFullBalance($arFields["Balance"], $arFields['ProviderCode'], $arFields['BalanceFormat'], false),
		"Login" => $arFields['Login'],
	);
	// correct balance
	if(is_null($groups[$arFields['DisplayName']]["Balance"]))
		if(($arFields["ErrorCode"] == ACCOUNT_CHECKED) || ($arFields["ErrorCode"] == ACCOUNT_WARNING) || ($arFields["ProviderID"] == ""))
			$groups[$arFields['DisplayName']]["Balance"] = "n/a";
		else
			$groups[$arFields['DisplayName']]["Balance"] = "Error";
	$account["Balance"] = $groups[$arFields['DisplayName']]["Balance"];
	$account["Owner"] = $groups[$arFields['DisplayName']]["Owner"];
	$account["ErrorCode"] = $arFields["ErrorCode"];
	if($arFields["ProviderID"] == "")
		$account["ErrorCode"] = strval(ACCOUNT_CHECKED);
	if($arFields['comment'] != '')
		$groups[$arFields['DisplayName']]['Comment'] = htmlspecialchars_decode($arFields['comment']);

	// Status
	$props = new TAccountInfo($arFields, ArrayVal($arFields, 'SubAccountID', null));
	if(isset($props->Number) && ($props->Number != $arFields['Login']))
		$groups[$arFields['DisplayName']][$props->NumberCaption] = $props->Number;
	if(isset($props->Status))
		$groups[$arFields['DisplayName']][$props->StatusCaption] = $props->Status;

	// Last Change
	$qHistory = new TQuery("select trim(trailing '.' from trim(trailing '0' from round(Balance, 7))) as Balance from AccountBalance where AccountID = {$arFields['ID']}
	".(isset($arFields['SubAccountID'])?" and SubAccountID = {$arFields['SubAccountID']}":" and SubAccountID is null")."
	and Balance ".($arFields['Balance'] != ''?"<> {$arFields['Balance']}":" is not null")." order by UpdateDate desc limit 1");
	if(!$qHistory->EOF){
		$change = formatFullBalance($arFields['Balance'] - $qHistory->Fields['Balance'], $arFields['ProviderCode'], $arFields['BalanceFormat'], true);
		if(($arFields['Balance'] - $qHistory->Fields['Balance']) > 0){
			$change = "+".$change;
			$account["Change"] = "inc";
		}
		else
			$account["Change"] = "dec";
		$account["ChangeValue"] = $change;
		$groups[$arFields['DisplayName']]['Last Change'] = $change;
	}
	if($arFields["UpdateDate"] != "")
		$groups[$arFields['DisplayName']]['Last Update'] = $arFields["UpdateDate"];
	// Expiration Date
	if($arFields['ExpirationDate'] != ''){
		$d = $Connection->SQLToDateTime($arFields["ExpirationDate"]);
		$sExpirationDate = date( DATE_FORMAT, $d );
		if($arFields["ProviderCode"] == "continental")
			$sExpirationDate .= ". Miles don't expire";
		if(isset($awPlus) && !$awPlus){
			if($datesShown >= 3)
				$sExpirationDate = AW_PLUS_ONLY;
			$datesShown++;
		}
		$groups[$arFields['DisplayName']]['Expiration Date'] = $sExpirationDate;
		$account['ExpirationDate'] = $sExpirationDate;
		$nExpires = ( time() - $d ) / SECONDS_PER_DAY;
		if( ( $nExpires > -90 ) && ( $nExpires <= 0 ) )
			$account["ExpirationState"] = "warning";
		if( $nExpires <= -90 )
			$account['ExpirationState'] = 'success';
		if( $nExpires > 0 )
			$account["ExpirationState"] = "error";
	}
	// properties
	$number = $arFields['Login'];
	$formatNumber = preg_match('/^\d+$/ims', $number);
	if(($arFields["ErrorCode"] == ACCOUNT_CHECKED) || ($arFields["ErrorCode"] == ACCOUNT_WARNING)){
		foreach($props->Rows as $row)
			if($row['Visible'] == '1'){
				$s = htmlspecialchars_decode($row['Val']);
				$values = hideCreditCards(array('Account' => $s), $arFields['Kind']);
				$s = array_shift($values);
				//hideCCNumber($s);
				if(isset($awPlus) && !$awPlus)
					$s = AW_PLUS_ONLY;
				$groups[$arFields['DisplayName']][$row['Name']] = $s;
			}
		if(isset($props->Number)){
			$number = htmlspecialchars_decode($props->Number);
			$values = hideCreditCards(array('Account' => $number), $arFields['Kind']);
			$number = array_shift($values);
			$formatNumber = is_numeric($number);
		}
	}
	// number and barcode
	if($request->Version >= 10){
		if($formatNumber)
			$account['Number'] = splitNumber($number);
		else
			$account['Number'] = $number;
		if($arFields['BarCode'] != ''){
			$drawing = createAccountBarCode($arFields, $arFields['BarCode'], $number);
			$im = $drawing->get_im();
			$barCode = "";
			$width = imagesx($im);
			for($n = 0; $n < $width; $n++){
				$pixel = imagecolorat($im, $n, 0);
				if($pixel == 0)
					$barCode .= "1";
				else
					$barCode .= "0";
			}
			$account['BarCode'] = trim($barCode, '0');
		}
	}
	// comment
	$groups = array_reverse($groups, true);
	$account['Groups'] = $groups;
}

function addLog($s){
	$r = fopen("/var/log/www/awardwallet/json".date("Y-m-d").".log", "a");
	fwrite($r, date("H:i:s")." [".getmypid()."] ".$s."\n");
	fclose($r);
}

function splitNumber($number){
	$result = "";
	while(strlen($number) > 0){
		if($result != "")
			$result .= " ";
		$result .= substr($number, 0, 3);
		$number = substr($number, 3);
	}
	return $result;
}

function getUserFields(){
	return array("Login", "FirstName", "LastName", "Email");
}

function getHost(){
	if($_SERVER['HTTP_HOST'] == 'cozi.awardwallet.com' || $_SERVER['HTTP_HOST'] == 'cozirewardwallet.com')
		return 'awardwallet.com';
	else
		return $_SERVER['HTTP_HOST'];
}


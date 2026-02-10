#!/usr/bin/php
<?
require __DIR__."/../../web/kernel/public.php";

echo "merging dividendmiles and aa\n";

$opts = getopt("hfu:l:b:p:d");
if(isset($opts['h']) || empty($opts['b']))
	die("usage
	".basename(__FILE__)." [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u: update scheduled accounts
	-l <limit>  : account limit
	-u <user id>: this user only
	-b <host> : backup source, mysql host name, you can incoude port, like 192.168.10.1:3306
	-p <pass> : backup password
	-d: delete trips only
");

$dividendmilesId = Lookup("Provider", "Code", "ProviderID", "'dividendmiles'", true);
$aaId = Lookup("Provider", "Code", "ProviderID", "'aa'", true);

$backup = new TMySQLConnection();
$backup->Open(["Host" => $opts['b'], "Login" => "awardwallet", "Database" => "awardwallet", "Password" => $opts['p']]);

$corrected = 0;

$sql = "select
	a.AccountID, a.Login, a.Login2, a.UserID, a.UserAgentID, a.PassChangeDate, a.SuccessCheckDate, a.ErrorCode, a.Pass, a.SavePassword, apn.Val as Number
from
	Account a
	left outer join AccountProperty apn on a.AccountID = apn.AccountID and apn.ProviderPropertyID = 24
where
	a.ProviderID = {$dividendmilesId}";

if (isset($opts['u']))
	$sql .= " and a.UserID = ".intval($opts['u']);
if (isset($opts['l']))
	$sql .= " limit ".intval($opts['l']);

echo "loading..\n";
$q = new TQuery($sql, $backup);
while (!$q->EOF) {
    if (processDividendmiles($q->Fields, $aaId, isset($opts['f'])))
		$corrected++;
	$q->Next();
}
$processed = $q->Position;

echo "looking for mismatched names\n";
$sql = "
select
 a.AccountID, a.Login, a.Login2, a.UserID, a.UserAgentID, a.PassChangeDate, a.SuccessCheckDate, a.ErrorCode, a.Pass, a.SavePassword, ap.Val as LastName
from
 Account a
 join AccountProperty ap on a.AccountID = ap.AccountID
where
 ap.ProviderPropertyID = 4358
 and a.ProviderID = 1
 and a.ErrorCode = 1
 and ap.Val <> a.Login2";
if (isset($opts['u']))
	$sql .= " and a.UserID = ".intval($opts['u']);
if (isset($opts['l']))
	$sql .= " limit ".intval($opts['l']);
$q = new TQuery($sql);
$correctedNames = 0;
while (!$q->EOF) {
    if (checkNames($q->Fields, isset($opts['f']), isset($opts['d'])))
		$correctedNames++;
	$q->Next();
}
$processed += $q->Position;

echo "done, processed {$processed} accounts, corrected: {$corrected}, corrected names: {$correctedNames}\n";

function processDividendmiles($fields, $aaId, $force) {
	global $Connection;
	echo "a:{$fields['AccountID']}, u:{$fields['UserID']}, l:{$fields['Login']}, n:{$fields['Number']}, p:".(empty($fields['Pass']) ? "saved" : "empty") . ", sp:{$fields['SavePassword']}";
	$result = false;
	$sql = "select
	p.Code, a.ProviderID, a.AccountID, a.Login, a.Login2, a.SavePassword, a.Pass, a.ErrorCode, a.PassChangeDate, a.SuccessCheckDate, a.UpdateDate
	from Account a
 	join Provider p on a.ProviderID = p.ProviderID
	where AccountID = ".$fields['AccountID'];
	$q = new TQuery($sql);
	if (!$q->EOF) {
		echo ", found account";
		if($q->Fields['ProviderID'] == $aaId) {
			echo ", this is aa";
			if(empty($q->Fields['Pass']))
				echo ", missing password";
			else
				echo ", password in place";
			if($fields['Login'] == $q->Fields['Login'] && !empty($fields['Number']) && $fields['Number'] != $fields['Login']){
				echo ", restoring login";
				$result = true;
				if($force)
					$Connection->Execute("update Account set Login = '" . addslashes($fields['Number']) . "' where AccountID = {$fields['AccountID']}");
			}
			if (!empty($fields['Pass']) && empty($q->Fields['Pass'])) {
				echo ", restoring password";
				if ($force) {
					$Connection->Execute("update Account set Pass = '" . addslashes($fields['Pass']) . "', SavePassword = " . SAVE_PASSWORD_DATABASE . " where AccountID = {$fields['AccountID']}");
				}
				$result = true;
				$q->Fields['Pass'] = $fields['Pass'];
			}
			if (aaPasswordValid($q->Fields) && $q->Fields['PassChangeDate'] < '2015-07-11 00:00:00') {
				echo ", resetting PassChangeDate";
				if($force)
					$Connection->Execute("update Account set PassChangeDate = '2015-07-11 00:00:00' where AccountID = {$fields['AccountID']}");
				$result = true;
			}
		}
		else{
			echo ", not aa ({$q->Fields['Code']}), skip";
		}
	}
	echo "\n";
	return $result;
}

function checkNames($fields, $force, $deleteTrips){
	global $Connection;
	echo "a:{$fields['AccountID']}, u:{$fields['UserID']}, l:{$fields['Login']}, p:".(empty($fields['Pass']) ? "saved" : "empty") . ", sp:{$fields['SavePassword']}";
	echo ", l2: {$fields['Login2']}, lastname: {$fields['LastName']}";
	if($deleteTrips){
		$Connection->Execute("delete from Trip where AccountID = {$fields['AccountID']} and CreateDate > '2015-06-20'");
		echo ", deleted trips: " . $Connection->GetAffectedRows();
	}
	else
		fixAccount($fields, $force);
//	$result = false;
//	if($force){
//		$Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
//	}
//	$result = true;
	echo "\n";
	return true;
}

function fixAccount($fields, $force){
	global $Connection;
	$q = new TQuery("select count(*) as Cnt from Trip where AccountID = {$fields['AccountID']} and CreateDate > '2015-06-20'");
	echo ", deleting trips ({$q->Fields['Cnt']}) and props";
	if($force) {
		$Connection->Execute("delete from Trip where AccountID = {$fields['AccountID']} and CreateDate > '2015-06-20'");
		echo ", deleted: " . $Connection->GetAffectedRows();
		$Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
		$Connection->Execute("delete from AccountBalance where AccountID = {$fields['AccountID']} and UpdateDate > '2015-06-20'");
		$qBalance = new TQuery("select max(AccountBalanceID) as AccountBalanceID from AccountBalance where AccountID = {$fields['AccountID']}");
		if(!empty($qBalance->Fields['AccountBalanceID'])){
			$qBalance = new TQuery("select * from AccountBalance where AccountBalanceID = " . $qBalance->Fields['AccountBalanceID']);
			echo ", correcting last balance";
			$Connection->Execute("update Account set LastChangeDate = '" . addslashes($qBalance->Fields['UpdateDate']) . "', LastBalance = {$qBalance->Fields['Balance']}, Balance = {$qBalance->Fields['Balance']} where AccountID = {$fields['AccountID']}");
		}
	}
	if(empty($fields['Pass'])){
		echo ", empty pass, can't check";
	}
	else{
		echo ", checking";
		if($force) {
			$options = CommonCheckAccountFactory::getDefaultOptions();
			$options->checkIts = true;
			$options->wsdlTimeout = 0;
			$options->timeout = 0;
			try {
				CommonCheckAccountFactory::checkAndSave($fields['AccountID'], $options);
			}
			catch(AccountException $e){
				echo ", " . $e->getMessage();
			}
			$Connection->Execute("update Account set LastChangeDate = null, LastBalance = null where AccountID = {$fields['AccountID']}");
		}
		$q->Open();
		echo ", trips: {$q->Fields['Cnt']}";
	}
}

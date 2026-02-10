<?php
require "../kernel/public.php";
require_once( "common.php" );
AuthorizeUser();
checkAjaxCSRF();

$accountId = intval(ArrayVal($_POST, "AccountID"));
$password = ArrayVal($_POST, "Password");
$toDatabase = ArrayVal($_POST, "ToDatabase") == 'true';

GetAgentFilters( $_SESSION['UserID'], $_SESSION['UserAgentID'], $sUserAgentAccountFilter, $sUserAgentCouponFilter, true );
$q = new TQuery("select a.* from Account a where a.AccountID = $accountId and ( $sUserAgentAccountFilter )");
if($q->EOF)
	die("Account $accountId not found");
if($q->Fields['ProviderID'] == 1 && $_SESSION['UserID'] == 152470)
	DieTrace("saving aa password, refs #8973", false, 0, $q->Fields);
if($toDatabase){
	$Connection->Execute("update Account set Pass = '".addslashes(CryptPassword($password))."', SavePassword = ".SAVE_PASSWORD_DATABASE." where AccountID = ".$accountId);
}
else{
	$lpm = getSymfonyContainer()->get("aw.manager.local_passwords_manager");
	$lpm->setPassword($accountId, $password);
}

echo "OK";

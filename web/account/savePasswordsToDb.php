<?
require __DIR__.'/../kernel/public.php';
require_once __DIR__.'/common.php';

AuthorizeUser();
checkAjaxCSRF();
GetAgentFilters( $_SESSION['UserID'], "All", $sUserAgentAccountFilter, $sUserAgentCouponFilter, true );

$accounts = explode(",", ArrayVal($_POST, 'accounts'));
$needPasswords = [];
foreach($accounts as $accountId){
	$accountId = intval($accountId);
	$q = new TQuery(AccountsSQL($_SESSION['UserID'], $sUserAgentAccountFilter, $sUserAgentCouponFilter, " and a.AccountID = $accountId", "", "All"));
	if ( $q->EOF )
		die("Access to this account is denied");
	if($q->Fields['SavePassword'] == SAVE_PASSWORD_LOCALLY){
		$loaded = false;
		$lpm = getSymfonyContainer()->get("aw.manager.local_passwords_manager");
		if($lpm->hasPassword($accountId))
			$Connection->Execute("update Account set SavePassword = ".SAVE_PASSWORD_DATABASE.", Pass = '".addslashes(CryptPassword($lpm->getPassword($accountId)))."'
			where AccountID = $accountId");
		else
			$needPasswords[] = ["AccountID" => $accountId, "ProviderName" => $q->Fields["ProviderName"], "Login" => $q->Fields["Login"], "UserName" => $q->Fields["UserName"]];
	}
}

header("Content-Type: application/json");
echo json_encode($needPasswords);
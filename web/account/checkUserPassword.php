<?php

// used for request password functionality, should migrate

require "../kernel/public.php";
require_once "$sPath/manager/passwordVault/common.php";
require_once "$sPath/account/common.php";

class checkUserPaswordPage {
	
	public $impersonated;
	public $userID;
	public $password;
	public $accountID;
	public $accountInfo = array();
	
	public function init() {
		$this->impersonated = requirePasswordAccess(true);
		$this->userID = $_SESSION["UserID"];
		$this->password = ArrayVal($_POST, "Password");
		$this->accountID = intval(ArrayVal($_POST, "AccountID"));
		$this->accountInfo = $this->getAccountInfo();
	}
	
	public function getAccountInfo() {
		GetAgentFilters($this->userID, "All", $sUserAgentAccountFilter, $sUserAgentCouponFilter, false, true, false);
		$sql = "
			SELECT a.*                             ,
			       uau.AgentID                     ,
			       p.DisplayName                   ,
			       p.Name AS ProviderName          ,
			       p.ProgramName                   ,
			       p.Site                          ,
			       p.Code                          ,
			       p.FAQ                           ,
			       uau.AccessLevel agentAccessLevel,
			       p.Currency                      ,
			       p.PasswordRequired              ,
			       p.CheckInBrowser
			FROM   Account a
			       LEFT OUTER JOIN Provider p
			       ON     a.ProviderID = p.ProviderID
			       LEFT OUTER JOIN UserAgent ua
			       ON     a.UserAgentID = ua.UserAgentID
			       LEFT OUTER JOIN UserAgent uau
			       ON     uau.ClientID    = a.UserID
			              AND uau.AgentID = {$this->userID}
			WHERE  (
			              $sUserAgentAccountFilter
			       )
			       AND a.AccountID = {$this->accountID}
			       AND
			       (
			              p.State IS NULL
			              OR p.State   >= ".PROVIDER_ENABLED.(ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG?"
			              OR p.State    = ".PROVIDER_TEST:"")."
			       )
		";
		$q = new TQuery($sql);
		if ($q->EOF)
			$this->SendResponse("Account not found");
		
		return $q->Fields;
	}
	
	public function SendResponse($error, $password = '') {
		$response = array(
			"Error"		=> $error,
			"Password"	=> $password,
		);
		header("Content-type: application/json");
		echo json_encode( $response );
		exit();
	}
	
}

$page = new checkUserPaswordPage();
# is logged in?
if (!isset($_SESSION['UserID']))
	$page->SendResponse('Not logged in');
# init
$page->init();
# access
$accountAccess = false;
if ($page->accountInfo["UserID"] == $page->userID || in_array($page->accountInfo["agentAccessLevel"], array(ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY)))
	$accountAccess = true;
if (!$accountAccess)
	$page->SendResponse("It's not your account");

if ($page->accountInfo["UserID"] != $page->userID && in_array($page->accountInfo["agentAccessLevel"], array(ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY)) && $page->accountInfo["AgentID"] != '') {
	$userID = $page->accountInfo["AgentID"];
} else
	$userID = $page->accountInfo["UserID"];
if (SITE_MODE == SITE_MODE_BUSINESS) {	
	if (isset($_SESSION['ManagerFields']['UserID'])/* && $page->accountInfo['UserID'] == $page->userID*/) {
		$userID = $_SESSION['ManagerFields']['UserID'];
	}
}

$um = getSymfonyContainer()->get("aw.manager.user_manager");
list($user, $error) = $um->checkUserLogin($userID, $page->password);

if (!isset($page->impersonated) && $error != "")
	$page->SendResponse($error);

if (isset($page->impersonated)){
	if ($page->accountInfo['SavePassword'] == SAVE_PASSWORD_LOCALLY)
		$page->SendResponse("Password saved locally. You can't request it");
	$pvId = requestPassword($page->accountID, \AwardWallet\MainBundle\Security\Utils::getImpersonator(getSymfonyContainer()->get("security.token_storage")->getToken()), $page->password);
	if(isset($pvId))
		$page->SendResponse("Request auto approved, id: $pvId");
	else
		$page->SendResponse("Request sent");
}
if ($page->accountInfo['SavePassword'] == SAVE_PASSWORD_LOCALLY) {
	$lpm = getSymfonyContainer()->get("aw.manager.local_passwords_manager");
	if ($lpm->hasPassword($page->accountID))
		$page->SendResponse("", $lpm->getPassword($page->accountID));
	else
		$page->SendResponse("Password is empty.");
}
	
$page->SendResponse("", getSymfonyContainer()->get(\AwardWallet\Common\PasswordCrypt\PasswordDecryptor::class)->decrypt($page->accountInfo['Pass']));


?>

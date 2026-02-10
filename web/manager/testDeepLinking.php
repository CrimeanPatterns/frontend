<?
$HTTP_SESSION_START = true;
$schema = "deepLinking";
require( "start.php" );
require_once "$sPath/kernel/TForm.php";
require_once "$sPath/wsdl/awardwallet/AwardWalletService.php";

drawHeader("Test deep linking");

setcookie(ini_get('session.name'), session_id(), time() + 60, "/manager/testDeepLinking.php", null, false, true);

$providerId = intval(ArrayVal($_GET, 'ProviderID'));
$qProv = new TQuery("select ProviderID, Code, DisplayName, Site from Provider where ProviderID = $providerId");
if($qProv->EOF)
	$Interface->DiePage("Provider $providerId not found");

$accounts = SQLToArray("select distinct
	pv.AccountID,
	concat(a.Login, ', ', u.FirstName, ' ', u.LastName) as Name
from
	PasswordVault pv
	join Account a on pv.AccountID = a.AccountID
	join PasswordVaultUser pvu on pv.PasswordVaultID = pvu.PasswordVaultID
	join Usr u on a.UserID = u.UserID
	join Provider p on a.ProviderID = p.ProviderID
where
	pvu.UserID = {$_SESSION['UserID']}
	and pv.ExpirationDate > now()
	and pv.Approved = 1
	and a.ProviderID = $providerId
	and (p.PasswordRequired = 0 or a.Pass <> '')
union
select
	a.AccountID,
	concat(a.Login, ', ', u.FirstName, ' ', u.LastName) as Name
from
	Account a
	join Usr u on a.UserID = u.UserID
where
	a.UserID in (7, 73657, 74996, 83755)
	and a.ProviderID = $providerId
	and a.SavePassword = ".SAVE_PASSWORD_DATABASE."
order by
	Name", "AccountID", "Name");

$form = new TForm(array(
	"AccountID" => array(
		"Type" => "integer",
		"Caption" => "Account",
		"Note" => "Password vault accounts for this provider. If this list is empty - request some passwords for this provider",
		"Options" => array("" => "") + $accounts,
		"RequiredGroup" => "Account",
	),
	"Login" => array(
		"Type" => "string",
		"Size" => 60,
		"RequiredGroup" => "Account",
		"Note" => "You can paste plain text login/pass here, if you have one",
	),
	"Pass" => array(
		"Type" => "string",
		"Size" => 60,
		"RequiredGroup" => "Account",
	),
	"TargetURL" => array(
		"Type" => "string",
		"Size" => 2000,
		"Required" => false,
		"Caption" => "Target URL",
		"Note" => "after autologin you will land on this page, for example: ".$qProv->Fields["Site"],
		//"Value" => $qProv->Fields["Site"]."/xxx",
		"InputAttributes" => "style='width: 700px;'",
	),
	"Using" => array(
		"Type" => "string",
		"Required" => true,
		"Caption" => "Login using",
		"Options" => array(
			"awardwallet" => "local",
			"service" => "wsdl",
		),
		"Value" => ArrayVal($_GET, 'Using', "service"),
	),
));
$form->SubmitButtonCaption = "Test";
$form->OnCheck = "checkFormParams";
$form->ButtonsAlign = "left";

if(isset($_SESSION['TestAutoLoginTime']) && (time() - $_SESSION['TestAutoLoginTime']) < 5){
	ob_clean();
	echo $_SESSION['TestAutoLogin'];
	unset($_SESSION['TestAutoLoginTime']);
	exit();
}

echo "<h2>{$qProv->Fields['DisplayName']}</h2>";
echo "<div>Goto <a href='{$qProv->Fields['Site']}' target='_blank'/>provider site</a> and select target page, copy and paste this target page to Target URL</div>";
echo "<h2>Please clear cookies for target site before testing</h2>";
echo "<h3>Select account from Password vault, or fill in login and password</h3>";

if($form->IsPost && $form->Check()){
	unset($_SESSION['TestAutoLoginTime']);
	if(!empty($form->Fields['AccountID']['Value'])){
		$qAcc = new TQuery("select Login, Login2, Login3, Pass from Account where AccountID = ".intval($form->Fields['AccountID']['Value']));
		$login = $qAcc->Fields["Login"];
		$login2 = $qAcc->Fields["Login2"];
		$login3 = $qAcc->Fields["Login3"];
		$pass = getSymfonyContainer()->get(\AwardWallet\Common\PasswordCrypt\PasswordDecryptor::class)->decrypt($qAcc->Fields["Pass"]);
	}
	else{
		$login = $form->Fields["Login"]['Value'];
		$login2 = '';
		$login3 = '';
		$pass = $form->Fields["Pass"]['Value'];
	}
	if($form->Fields['Using']['Value'] == 'service')
		loginUsingService(
			$qProv->Fields['Code'],
			$login,
			$login2,
			$login3,
			$pass,
			$form->Fields['TargetURL']['Value']
		);
	else{
		loginUsingAwardwallet($form->Fields['AccountID']['Value']);
	}
}

echo $form->HTML();

drawFooter();

function loginUsingAwardwallet($accountId){
	getSymfonyContainer()->get("session")->set('AllowRedirectTo', $accountId);
	echo "<a href=\"" . getSymfonyContainer()->get("router")->generate("aw_account_redirect", ["ID" => $accountId])."\" target=\"_blank\">Click to open a new autologin window</a>";
}

function loginUsingService($providerCode, $login, $login2, $login3, $pass, $targetUrl){
	$params = array(
		'exceptions' => true,
		'trace' => true,
		'location'	 => getSymfonyContainer()->getParameter("wsdl.address"),
		'wsse-login' => getSymfonyContainer()->getParameter("wsdl.login"),
		'wsse-password' => getSymfonyContainer()->getParameter("wsdl.password")
	);
	$aw = new AwardWalletService($params, WSDL_SERVER_ADDRESS."?wsdl");
	try{
		$response = $aw->PrepareRedirect(new PrepareRedirectRequest(
			WSDL_API_VERSION,
			$providerCode,
			$login,
			$login2,
			$login3,
			$pass,
			$targetUrl,
			"test",
			null,
			ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_PRODUCTION
		));
	}
	catch(SoapFault $e){
		echo "SoapFault exception: ".$e->getMessage()."<br>";
		echo "Response:<br><pre>".htmlspecialchars($aw->__getLastResponseHeaders().$aw->__getLastResponse())."</pre>";
	}
	if(isset($response)){
		ob_clean();
		$_SESSION['TestAutoLogin'] = $response->Response;
		$_SESSION['TestAutoLoginTime'] = time();
		echo $response->Response;
		exit();
	}
}

function checkFormParams(){
	global $form;
	if(($form->Fields['Using']['Value'] == 'awardwallet') && empty($form->Fields['AccountID']['Value']))
		return "AccountID required for logging in through AwardWallet";
	return null;
}

?>

<?
$schema = "callbackCheckAccount";
require( "start.php" );
require_once "$sPath/kernel/TForm.php";
require_once "$sPath/wsdl/awardwallet/AwardWalletService.php";
require_once "$sPath/api/awardwallet/CallbackService.php";

ini_set("soap.wsdl_cache_enabled", "0");

drawHeader("WSDL Check Account");

define("WSDL_ERROR__NOT_FOUND_ACCOUNT", 100);

define('CALLBACK_CHECK', 'http://'.$_SERVER['HTTP_HOST'].'/api/awardwallet/callback.php');
define('WSDL_LOCAL', 'http://wsdl.awardwallet.local/wsdl/');
define('WSDL_SERVER_1', 'https://service.awardwallet.com/wsdl/');

$callbacks = array(
	WSDL_LOCAL 		=> 'http://awardwallet.local/api/awardwallet/callback.php',
	WSDL_SERVER_1	=> 'https://awardwallet.com/api/awardwallet/callback.php',
);

define('ASYNC_TYPE', 0);
define('SYNC_TYPE', 1);

$form = new TForm(array(
	"AccountID" => array(
		"Type" => "string",
		"Size" => 60,
		"Caption" => "Account ID",
		"RegExp" => "/^\d+$/ims",
		"RegExpErrorMessage" => "Account ID must be an integer",
		"Value" => "",
		"Required" => true,
	),
	"Server" => array(
		"Type" => "string",
		"InputType" => "select",
		"Caption" => "Server",
		"Options" => array(
			WSDL_SERVER_1 => WSDL_SERVER_1,
			WSDL_LOCAL => WSDL_LOCAL,
		),
		"Value" => (ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG) ? WSDL_LOCAL : WSDL_SERVER_1,
		"Required" => true,
	),
	"Timeout" => array(
		"Type" => "string",
		"Caption" => "Timeout (sec.)",
		"RegExp" => "/^\d+$/ims",
		"RegExpErrorMessage" => "Timeout must be an integer",
		"Value" => 45,
		"Required" => true,
	),
	"Type" => array(
		"Type" => "string",
		"InputType" => "select",
		"Caption" => "Type of test",
		"Options" => array(
			ASYNC_TYPE 	=> 'Asynchronous (with callback)',
			SYNC_TYPE 	=> 'Synchronous (without callback)',
		),
		"Required" => true,
	),
));
$form->SubmitButtonCaption = "Check";
$form->ButtonsAlign = "left";

if($form->IsPost && $form->Check()){
	$type = $form->Fields["Type"]["Value"];
	$options = array(
		"connection_timeout" => ($type == SYNC_TYPE) ? $form->Fields["Timeout"]["Value"] : 0,
	);
	if ($form->Fields['Server']['Value'] == WSDL_LOCAL && defined('DEBUG_PROXY_HTTPAUTH_USERNAME') && defined('DEBUG_PROXY_HTTPAUTH_PASSWORD')) {
		$options["login"] = DEBUG_PROXY_HTTPAUTH_USERNAME;
		$options["password"] = DEBUG_PROXY_HTTPAUTH_PASSWORD;
	}
	
	try {
		$response = WSDLCheckAccount($form->Fields["AccountID"]["Value"], $form->Fields["Server"]["Value"], $options, 5, true, null, true);
		if ($type == SYNC_TYPE && $response instanceof CheckAccountResponse) {
			# Save
			$service = new CallbackService();
			$result = $service->CheckAccountCallback($response);
		}
	} catch (Exception $e) {
		echo "<strong>".$e->getMessage()."</strong><br />";
	}
}

echo $form->HTML();

if (isset($response)) {
	echo '<div id="ResultField">';
	$Interface->drawSectionDivider("Result");
		echo "<pre>";
			ob_start();
			var_dump($response);
			$content = ob_get_contents();
			ob_end_clean();
			echo htmlspecialchars($content);
		echo "</pre>";
	echo '</div>';
}

echo "
<script type=\"text/javascript\">
$(document).ready(function() {
	$('div.overallButton[name=\"submitButtonTrigger\"] table td:eq(1)').append('<img id=\"progressBar\" style=\"vertical-align: middle; margin: 2px 4px 4px; display: none\" src=\"/lib/images/progressCircle.gif\" alt=\"loading\" />');
	$('form[name=\"editor_form\"]').submit(function() {
		$('#progressBar').show();
		$('#ResultField').remove();
	});
	$('#fldType').change(function (){
		if ($(this).val() == ".ASYNC_TYPE.") {
			$('#fldTimeout').attr('disabled', 'disabled');
		} else {
			$('#fldTimeout').removeAttr('disabled');
		}
	});
});
</script>
";

drawFooter();

function WSDLCheckAccount($accountID, $server, $options = array(), $priority = 5, $parseIt = true, $browserState = null, $parseHistory = false){
	global $callbacks;
	$row = SQLToArray($sql = "
		SELECT 
			   p.Code,
			   a.Login,
			   a.Login2,
			   a.Login3,
			   a.Pass,
			   a.UserID
		FROM Account a 
			JOIN Provider p ON a.ProviderID = p.ProviderID
		WHERE a.AccountID = ".$accountID." AND 
			  a.State = ".ACCOUNT_ENABLED." AND 
			  a.SavePassword = ".SAVE_PASSWORD_DATABASE."",
		"Code", "Login", true);
	if (!sizeof($row))
		throw new Exception("Account not found", WSDL_ERROR__NOT_FOUND_ACCOUNT);		
	
	$row = $row[0];
	$response = null;
	$markCoupons = null;
	$answers = null;
	$timeout = $options['connection_timeout'];
	unset($options['connection_timeout']);
	
	$params = array(
		'exceptions' => true,
		'trace' => true,
		'location'	 => $server,
		'wsse-login' => 'test',
		'wsse-password' => WSDL_TEST_PASSWORD
	);
	$params = array_merge($params, $options);
	$aw = new AwardWalletService($params, $server."?wsdl");	

	//$callbacks = array("") + SQLToSimpleArray("select URL from PartnerCallback order by URL", "URL");
	if ($timeout == 0)
		$callbackURL = $callbacks[$server];
	else
		$callbackURL = '';
	try {
		$response = $aw->CheckAccount($request = new CheckAccountRequest(
				WSDL_API_VERSION,
				$row['Code'],
				$row['Login'],
				$row['Login2'],
				$row['Login3'],
                getSymfonyContainer()->get(\AwardWallet\Common\PasswordCrypt\PasswordDecryptor::class)->decrypt($row['Pass']),
				'',
				'',
				true,
				$timeout,
				$priority,
				$callbackURL,
				0,
				$parseIt,
				$row['UserID'],
				'',
				$markCoupons,
				$answers,
			  	$browserState,
				$parseHistory,
				null,
				null,
				null
		));
	} catch(SoapFault $e){
		echo "<strong>".$e->getMessage()."</strong><br />";
		echo "Response:<br>".$aw->__getLastResponseHeaders().$aw->__getLastResponse();
	}
	
	return $response;
}

?>

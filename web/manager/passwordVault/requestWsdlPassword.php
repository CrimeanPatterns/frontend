<?
$schema = "passwords";
require("../start.php");
require_once "$sPath/kernel/TForm.php";
require_once "$sPath/manager/passwordVault/common.php";
require_once "$sPath/wsdl/debug/AwardWalletDebugService.php";

drawHeader("Request WSDL password");

$form = new TForm(array(
	"Partner" => array(
		"Type" => "string",
		"RequiredGroup" => "partner",
	),
	"Provider" => array(
		"Type" => "string",
		"Options" => array_merge(array("" => "Select"), SQLToArray("select Code, DisplayName from Provider where State >= ".PROVIDER_ENABLED." or State = ".PROVIDER_TEST."  order by DisplayName", "Code", "DisplayName")),
		"RequiredGroup" => "partner",
	),
	"Login" => array(
		"Type" => "string",
	),
	"Note" => array(
		"Type" => "string",
	),
	"Delete" => array(
		"Type" => "boolean",
	),
));
$form->SubmitButtonCaption = "Request password";

if($form->IsPost && $form->Check()){
	$server = getSymfonyContainer()->getParameter("wsdl.debug_address");

	$params = array(
		'exceptions' => true,
		'trace' => true,
		'location'	 => $server,
		'wsse-login' => getSymfonyContainer()->getParameter("wsdl.login"),
		'wsse-password' => getSymfonyContainer()->getParameter("wsdl.password"),
	);
	ini_set("soap.wsdl_cache_enabled", "0");
	$aw = new AwardWalletDebugService($params, $server."?wsdl");
	$request = new RequestAccountPasswordRequest(
		$form->Fields['Partner']['Value'],
		$form->Fields['Provider']['Value'],
		$form->Fields['Login']['Value'],
		$form->Fields['Note']['Value'],
		$form->Fields['Delete']['Value'] == '1'
	);
	try{
		$result = $aw->RequestAccountPassword($request);
		if($form->Fields['Delete']['Value']) {
			if($result->Exists)
				echo "<div class='successFrm'>Password request deleted</div>";
			else
				echo "<div class='successFrm'>Password request does not exists</div>";
		}
		else {
			if ($result->Exists)
				echo "<div class='successFrm'>Password request updated</div>";
			else
				echo "<div class='successFrm'>Password request created</div>";
		}
		echo '<br/>';
	}
	catch(SoapFault $e){
		echo "<div class='errorFrm'>".$e->getMessage()."</div>";
//		echo "SoapFault exception: ".$e->getMessage()."<br>";
		echo "Response:<br>".$aw->__getLastResponseHeaders().$aw->__getLastResponse();
	}
}
echo $form->HTML();

drawFooter();

?>

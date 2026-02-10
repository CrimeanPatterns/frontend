<?php
$bNoSession = true;
require("../../../kernel/public.php");
require("wsdl/AwardWalletDebugService.php");
require("../../../manager/passwordVault/common.php");

ini_set("soap.wsdl_cache_enabled", "0");

$server = new SoapServer("wsdl/debug.wsdl", array('classmap' => array(
	"SavePasswordRequest" => "SavePasswordRequest",
	"SavePasswordResponse" => "SavePasswordResponse",
)));

class DebugService extends SoapService{

	function Authenticate(){
		$this->ReadWsseSecurity();
		$container = getSymfonyContainer();
		if($this->UserName != $container->getParameter("wsdl_debug_login") || $this->Password != $container->getParameter("wsdl_debug_password"))
			return 'Check your username or password';
		else
			return null;
	}

	function SavePassword(SavePasswordRequest $request){
		$providerId = Lookup("Provider", "Code", "ProviderID", "'".addslashes($request->Provider)."'", true);
		$id = addToPasswordVault($providerId, $request->Login, $request->Login2, $request->Login3, $request->Password, null, $request->Partner, json_decode($request->Answers, true));
		if($id)
			mailTo(
				"alexi@awardwallet.com, vladimir@awardwallet.com",
				"Received WSDL password request",
				"Approve this record in password vault:
http://{$_SERVER['HTTP_HOST']}/lib/admin/table/list.php?Schema=PasswordVault&Approved=0

and share:
http://awardwallet.com/manager/passwordVault/get.php?ID={$id}",
				EMAIL_HEADERS
			);
		return new SavePasswordResponse(true);
	}

}

$service = new DebugService();
$server->setObject($service);
if(isset($_GET['wsdl']) && ($_SERVER['REQUEST_METHOD'] == 'GET'))
	$server->handle();
else{
	$error = $service->Authenticate();
	if(isset($error)){
		$server->fault("AccessDenied", $error);
		exit();
	}
	try{
		$server->handle();
	}
	catch(Exception $e){
		$server->fault("Error", $e->getMessage());
		exit();
	}
}

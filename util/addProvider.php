<?php
require "../web/kernel/public.php";

if(count($argv) != 2)
	die("usage: addProvider.php <providercode>\n");

$code = strtolower($argv[1]);
echo "adding provider $code\n";
$q = new TQuery("select * from Provider where Code = '".addslashes($code)."'");
if($q->EOF)
	die("provider not found in database\n");
$props = SQLToArray("select Code, Name from ProviderProperty where ProviderID = {$q->Fields['ProviderID']} order by SortIndex", "Code", "Name");
if(count($props) == 0){
	if(strtolower(readline("no properties found for provider. continue? (y/n): ")) != "y")
		die("cancelled\n");
}
if(!file_exists("../web/engine/$code"))
	if(!mkdir("../web/engine/$code"))
		die("failed to create dir\n");
$ucfCode = ucfirst($code);

$loginName = readline("login input name: ");
$passName = readline("password input name: ");
$formName = readline("form name or id: ");
$errorExp = readline("login error xpath: ");
$balanceExp = readline("balance xpath: ");
$propertyExp = readline("property xpath ([Code] and [Name] macros will be replaced): ");

$propsSrc = "";
foreach($props as $propCode => $name){
	$exp = str_ireplace('[Code]', $propCode, $propertyExp);
	$exp = str_ireplace('[Name]', $name, $exp);
	$propsSrc .= "\n".'		$this->SetProperty("'.$propCode.'", $this->http->'.functionByExp($exp).'("'.$exp.'")); // '.$name;
}

$src = '<?php

class TAccountChecker'.$ucfCode.' extends TAccountChecker{

	function LoadLoginForm(){
		$this->http->getCookieManager()->reset();
		$this->http->GetURL("'.$q->Fields['LoginURL'].'");
		if(!$this->http->ParseForm("'.$formName.'"))
			return false;
		$this->http->Form[\''.$loginName.'\'] = $this->AccountFields[\'Login\'];
		$this->http->Form[\''.$passName.'\'] = $this->AccountFields[\'Pass\'];
		//$this->http->Form[\'__EVENTTARGET\'] = \'\';
		return true;
	}

	function Login(){
		$this->http->PostForm();
		$error = $this->http->'.functionByExp($errorExp).'("'.$errorExp.'", null, false);
		if(isset($error)){
			$this->ErrorCode = ACCOUNT_INVALID_PASSWORD;
			$this->ErrorMessage = $error;
			return false;
		}
		return true;
	}

	function Parse(){
		$this->SetBalance($this->http->'.functionByExp($balanceExp).'("'.$balanceExp.'"));'.$propsSrc.'
	}

	function GetRedirectParams(){
		$arg = parent::GetRedirectParams();
		$arg["CookieURL"] = "'.$q->Fields['LoginURL'].'";
		return $arg;
	}

}

?>';

file_put_contents("../web/engine/$code/functions.php", $src);
echo "done\n";

function functionByExp($exp){
	if(preg_match('/^\/.*\/ims$/ims', $exp))
		return 'FindPreg';
	else
		return 'FindSingleNode';
}

?>

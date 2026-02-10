<?php

$bNoSession = true;
require_once __DIR__."/../kernel/public.php";
require_once __DIR__."/../account/common.php";
require_once "$sPath/lib/classes/TBaseFormEngConstants.php";

$sTitle = "Debug Change Account Password";

require "$sPath/lib/admin/design/header.php";

$objForm = new TBaseForm([
	'ProviderID' => [
		"Caption" => "Provider",
		"Type" => "integer",
		"Options" =>  ["" => ""] + SQLToArray("SELECT ProviderID, `Code` FROM Provider WHERE CanChangePasswordServer = 1 ORDER BY Code", "ProviderID", "Code"),
		"Required" => true,
	],
    "Login" => [
        "Type" => "string",
        "Required" => true,
    ],
    "Login2" => [
        "Type" => "string",
    ],
    "Login3" => [
        "Type" => "string",
    ],
    "Password" => [
        "Type" => "string",
        "Required" => true,
    ],
    "NewPassword" => [
        "Type" => "string",
        "Required" => true,
    ],
    "ShowHeaders" => [
		"Type" => "boolean",
		"InputType" => "checkbox",
		"Caption" => "Show headers",
		"Value" => false,
		"Required" => true,
	],
]);
$objForm->CsrfEnabled = false;
$objForm->SubmitButtonCaption = "Change Account Password";

if ($objForm->IsPost && $objForm->Check()) {
	$providerId = (int)$objForm->Fields["ProviderID"]["Value"];

	$q = new TQuery("SELECT Code, Engine, CanChangePasswordServer FROM Provider WHERE ProviderID = $providerId");
	$providerCode = $q->Fields['Code'];
	$newPassword = $objForm->Fields["NewPassword"]["Value"];
	if (!file_exists($sPath."/engine/".$providerCode."/functions.php")) {
		echo "<strong>Your local copy is out of date. Provider is not found.</strong><br />";
	} else {
		$accountInfo = [
            'ProviderCode' => $providerCode,
            'CanChangePasswordServer' => $q->Fields['CanChangePasswordServer'],
            'Login' => $objForm->Fields["Login"]["Value"],
            'Login2' => $objForm->Fields["Login2"]["Value"],
            'Login3' => $objForm->Fields["Login3"]["Value"],
            'Pass' => $objForm->Fields["Password"]["Value"],
        ];
		$checker = getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\CheckerFactory::class)->getAccountChecker($providerCode, false, $accountInfo);
        $doctrineConnection = getSymfonyContainer()->get('database_connection');
        $checker->db = new DatabaseHelper($doctrineConnection);
        $checker->onLoggedIn = function() use($checker, $newPassword) {
            $checker->ChangePassword($newPassword);
        };
		$objForm->OnCheck = "CheckForm";
	}
}
if (isset($providerId) && isset($checker))
	if($objForm->IsPost)
		$objForm->Check();

function CheckForm() {
    /** @var TAccountChecker $checker */
	global $objForm, $checker, $providerCode;

	$arFields = array_diff_key($objForm->GetFieldValues(), array('ProviderID' => true, 'ShowHeaders' => true));

	$checker->KeepLogs = true;
	$checker->LogMode = 'dir';
	$checker->InitBrowser();
	$checker->http->LogHeaders = $objForm->Fields["ShowHeaders"]["Value"];

	$msg = $checker->Check();
	$checker->http->LogSplitter();

	echo '<strong>Log: </strong><a href="/admin/common/logFile.php?Dir='.urlencode($checker->http->LogDir).'&File='.urlencode("log.html").'" target="_blank">'.$checker->http->LogDir.'/log.html</a><br /><br />';
}

echo $objForm->HTML();

require "$sPath/lib/admin/design/footer.php";

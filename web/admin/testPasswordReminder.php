<?php

require __DIR__."/../kernel/public.php";
require_once "$sPath/lib/classes/TBaseFormEngConstants.php";
$sTitle = "Password reminder tester";

require "$sPath/lib/admin/design/header.php";

$form = new TBaseForm(array(
	"ProviderCode" => array(
		"Type" => "string",
		"Required" => true,
		"Value" => "marriott",
	),
	"Email" => array(
		"Type" => "string",
		"Value" => ArrayVal($_SESSION, 'Email'),
		"Required" => true,
	),
));
$form->SubmitButtonCaption = "Send reminder";

if($form->IsPost && $form->Check()){
	$checker = getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\CheckerFactory::class)->getAccountChecker($form->Fields['ProviderCode']['Value'], true);
	$checker->InitBrowser();
	$checker->KeepLogs = true;
	$checker->http->LogMode = 'dir';
	if($checker->RetrievePassword($form->Fields['Email']['Value']))
		echo "success<br/>";
	else
		echo "failure<br/>";
	echo 'Log: <a href="/admin/common/logFile.php?Dir='.urlencode($checker->http->LogDir).'&File='.urlencode("log.html").'" target="_blank">'.$checker->http->LogDir.'/log.html</a><br />';
}

echo $form->HTML();
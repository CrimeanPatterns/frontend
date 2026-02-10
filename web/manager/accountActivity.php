<?php
$schema = "logs";
require "start.php";

drawHeader("Account activity", "Calc account activity score");

$form = new TForm(array(
	"AccountID" => array(
		"Caption" => "Account ID",
		"RequiredGroup" => true,
		"Type" => "integer",
	),
));
$form->SubmitButtonCaption = "Calc";
$form->ButtonsAlign = "left";

if($form->IsPost)
	$form->Check();
echo $form->HTML();

if($form->IsPost && !isset($form->Error)){
	$accountId = intval($form->Fields['AccountID']['Value']);
	echo "Activity for account $accountId:<br/>";
	echo "<pre>".json_encode(getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\BackgroundCheckScheduler::class)->getAccountNextCheck($accountId, 'details'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."</pre>";
	echo "Check period: ".(getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\BackgroundCheckScheduler::class)->getAccountNextCheck($accountId)['ActivityScore'] / 24)." days";
}

drawFooter();

?>

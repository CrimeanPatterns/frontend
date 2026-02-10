<?
require "../../kernel/public.php";
require "$sPath/kernel/TForm.php";
require_once "$sPath/account/common.php";

$sTitle = "Check account";

$form = new TForm(array(
	"AccountID" => array(
		"Type" => "integer",
		"Required" => true,
		"Caption" => "Account ID",
	),
	"CheckReservations" => array(
		"Type" => "boolean",
		"Required" => true,
		"Value" => "1",
	),
	"Output" => array(
		"Type" => "string",
		"Required" => true,
		"Value" => "log",
		"Options" => array(
			"log" => "Show log",
			"database" => "Save to database",
		)
	),
));
$form->SubmitButtonCaption = "Check";

require "$sPath/lib/admin/design/header.php";

echo $form->HTML();

if($form->IsPost && $form->Check()){
	try {
		$options = CommonCheckAccountFactory::getDefaultOptions();
		$options->checkIts = $form->Fields['CheckReservations']['Value'];
		$options->preventLockouts = false;
		$auditor = CommonCheckAccountFactory::getAccountAuditorByEnvironment($form->Fields['AccountID']['Value'], $options);
		$auditor->check();
		$report = $auditor->getReport();
		$auditor->save($auditor->getAccount(), $report, $auditor->getCheckOptions());
	} catch (Exception $e) {
		if (!processCheckException($e))
			DieTrace($e->getMessage(), false);
		else
			die($e->getMessage());
	}
	if($form->Fields['Output']['Value'] == 'log'){
		$logFile = $report->logPath."/log.html";
		if(file_exists($logFile))
			Redirect("/admin/common/logFile.php?Dir=".urlencode($report->logPath)."&File=".urlencode("log.html"));
		else
			echo("log not found");
	}
	else
		echo "saved to database. result: true";
}

require "$sPath/lib/admin/design/footer.php";


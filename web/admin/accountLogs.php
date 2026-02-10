<?php

use Aws\S3\S3Client;

require_once __DIR__."/../kernel/public.php";

$sTitle = "Account logs";

require __DIR__ . "/../lib/admin/design/header.php";

echo "<h1>Search for logs of account</h1>";

$form = new TForm(array(
	"Partner" => array(
		"Required" => true,
		"Type" => "string",
		"Size" => 40,
		"Value" => "awardwallet",
	),
	"AccountID" => array(
		"Caption" => "Account ID",
		"RequiredGroup" => true,
		"Type" => "integer",
		"Value" => ArrayVal($_GET, 'AccountID'),
	),
	"ProviderCode" => array(
		"RequiredGroup" => true,
		"Type" => "string",
		"Size" => 40,
	),
	"Login" => array(
		"Caption" => "Login or ConfNo",
		"RequiredGroup" => true,
		"Type" => "string",
		"Size" => 40,
	),
	"Login2" => array(
		"RequiredGroup" => true,
		"Type" => "string",
		"Size" => 40,
	),
	"Login3" => array(
		"RequiredGroup" => true,
		"Type" => "string",
		"Size" => 40,
	),
));
$form->SubmitButtonCaption = "Search logs";
$form->ButtonsAlign = "left";
$form->CsrfEnabled = false;
$form->OnCheck = function() use($form){
	if(empty($form->Fields['AccountID']['Value']) && empty($form->Fields['ProviderCode']['Value']))
		return "AccountID or (Provider Code + Login) required";
	return null;
};

if($form->IsPost)
	$form->Check();

echo "<script type='text/javascript'>
$(function(){
	$('#fldPartner').click(function () {
		$(this).select();
	});
	$('#fldAccountID').click(function () {
		$(this).select();
	});
	$('#fldProviderCode').click(function () {
		$(this).select();
	});
	$('#fldLogin').click(function () {
		$(this).select();
	});
	$('#fldLogin2').click(function () {
		$(this).select();
	});
	$('#fldLogin3').click(function () {
		$(this).select();
	});
});
</script>";

echo $form->HTML();

$router = getSymfonyContainer()->get("router");

if(($form->IsPost && !isset($form->Error)) || isset($_GET['AccountID'])){
	$accountId = intval($form->Fields['AccountID']['Value']);
	$logs = getSymfonyContainer()->get(\AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface::class)->getLogs($form->Fields['Partner']['Value'], $accountId, $form->Fields['ProviderCode']['Value'], $form->Fields['Login']['Value'], $form->Fields['Login2']['Value'], $form->Fields['Login3']['Value']);
    $logs = array_merge($logs, getAccountLogsFromS3($accountId));
	if(empty($accountId) && !empty($form->Fields['ProviderCode']['Value']))
	    $logs = array_merge($logs, getConfNoLogs($form->Fields['ProviderCode']['Value'], $form->Fields['Login']['Value']));

	usort($logs, function($log1, $log2){
		return getLogDate($log2) - getLogDate($log1); // reverse
	});

	$router = getSymfonyContainer()->get("router");

	if(count($logs) == 0)
		echo "No logs found";
	else{
		if(isset($_GET['ShowLatest']) && count($logs) > 0){
			$log = array_shift($logs);
            $redirectUrl = $router->generate("aw_manager_loyalty_local_log", [
                "file" => FileName(basename($log)),
                "Index" => 0,
                "AccountID" => $accountId
            ]);
            Redirect($redirectUrl);
		}
		echo "<table class='detailsTable' cellpadding='2'>";
		foreach($logs as $log){
			$date = date(FORM_DATE_TIME_FORMAT, getLogDate($log));
            $href = $router->generate("aw_manager_loyalty_local_log", ["file" => FileName(basename($log)), "Index" => 0, "AccountID" => $accountId]);
            $name = basename($log);
			echo "
			<tr>
				<td>".$date."</td>
				<td><a href='".$href."' target='_blank'>".$name."</a></td>
			</tr>";
		}
		echo "</table>";
	}
}

require __DIR__ . "/../lib/admin/design/footer.php";

function getLogDate($log){
	$date = filemtime($log);
	if(preg_match("/account\-\d+\-(\d+)\.zip/ims", basename($log), $matches))
		$date = $matches[1];
	elseif(preg_match("/account\-.*\-(\d{7,20}+)\.zip/ims", basename($log), $matches))
		$date = $matches[1];
	return $date;
}

function getConfNoLogs($providerCode, $confNo){
	$mask = getSymfonyContainer()->getParameter("checker_logs_dir") . "/check-{$providerCode}";
	if(!empty($confNo))
		$mask .= "-$confNo";
	$mask .= "-*";
	$logs = glob($mask);
	return $logs;
}

function getAccountLogsFromS3($accountId){
	// autoloading
	getSymfonyContainer();

	$client = getSymfonyContainer()->get(S3Client::class);

	$iterator = $client->getIterator('ListObjects', array('Bucket' => 'awardwallet-logs', 'Prefix' => "account-{$accountId}-"));

    $result = [];
	foreach ($iterator as $object) {
		$filename = getSymfonyContainer()->getParameter("checker_logs_dir") . "/".sprintf("%03d", floor($accountId / 1000))."/".$object['Key'];
        $result[] = $filename;
		if(!file_exists($filename)){
			$dir = dirname($filename);
			if(!file_exists($dir))
				mkdir($dir, 0777, true);
			$client->getObject(array(
				'Bucket' => 'awardwallet-logs',
				'Key' => $object['Key'],
				'SaveAs' => $filename
			));
		}
	}

	return $result;
}


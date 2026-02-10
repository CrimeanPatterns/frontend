<?php

use Aws\S3\S3Client;

$schema = "logs";
require "start.php";
require_once "$sPath/kernel/TForm.php";

drawHeader("Transfer logs");

echo "<h1>Search for logs of transfer methods</h1>";

$form = new TForm(array(
	"Partner" => array(
		"Required" => true,
		"RequiredGroup" => false,
		"Type" => "string",
		"Size" => 40,
		"Value" => "awardwallet",
	),
	"ProviderCode" => array(
		"Required" => true,
		"RequiredGroup" => false,
		"Type" => "string",
		"Size" => 40,
	),
	"Method" => array(
		"Caption" => "Method",
		"Required" => true,
		"RequiredGroup" => false,
		"Type" => "string",
		"Size" => 40,
        "Options" => [
                'register' => "register",
                "transfer" => "transfer",
                "purchase" => "purchase",
            ],
	),
	"RequestId" => array(
		"RequiredGroup" => false,
		"Type" => "string",
		"Size" => 40,
	),
));
$form->SubmitButtonCaption = "Search logs";
$form->ButtonsAlign = "left";
$form->OnCheck = function() use($form){
	if(empty($form->Fields['Partner']['Value']))
		return "Partner required";
	return null;
};

if($form->IsPost)
	$form->Check();

echo "<script type='text/javascript'>
$(function(){
	$('#fldPartner').click(function () {
		$(this).select();
	});
	$('#fldMethod').click(function () {
		$(this).select();
	});
	$('#fldProviderCode').click(function () {
		$(this).select();
	});
	$('#fldRequestId').click(function () {
		$(this).select();
	});
});
</script>";

echo $form->HTML();

if($form->IsPost && !isset($form->Error)){
	$fields['partner'] = $form->Fields['Partner']['Value'];
	$fields['provider'] = $form->Fields['ProviderCode']['Value'];
	$fields['method'] = $form->Fields['Method']['Value'];
//	$fields['provider'] = empty($form->Fields['ProviderCode']['Value']) ? '\w+' : $form->Fields['ProviderCode']['Value'];
//	$fields['method'] = empty($form->Fields['Method']['Value']) ? '\w+' : $form->Fields['Method']['Value'];
	$fields['id'] = empty($form->Fields['RequestId']['Value']) ? '\w+' : $form->Fields['RequestId']['Value'];

	$logs = [];
	getAccountLogsFromS3($fields);
	$logs = getTransferLogFiles($fields);

	if(count($logs) == 0)
		echo "No logs found";
	else{
		echo "<table class='detailsTable' cellpadding='2'>";
		foreach($logs as $log){
			$date = date(FORM_DATE_TIME_FORMAT, getLogDate($log));
			echo "
			<tr>
				<td>".$date."</td>
				<td><a href='accountLog.php?File=".urlencode(FileName(basename($log)))."&Index=0' target='_blank'>".basename($log)."</a></td>
			</tr>";
		}
		echo "</table>";
	}
}

drawFooter();

function getTransferLogFiles($fields){
	$logs = [];
	$dir = '\/var\/log\/www\/awardwallet\/checklogs\/';
	foreach(glob("{$dir}restapi_{$fields['partner']}_*") as $log)
		if(preg_match("/^{$dir}restapi_{$fields['partner']}_{$fields['provider']}_{$fields['method']}_{$fields['id']}\.zip$/i", $log))
			$logs[] = $log;

	$logs = array_reverse($logs);
	return $logs;
}

function getLogDate($log){
	$date = filemtime($log);
	if(preg_match("/account\-.+\-(\d+)\.zip/ims", basename($log), $matches))
		$date = $matches[1];
	return $date;
}

function getConfNoLogs($providerCode, $confNo){
	$mask = sys_get_temp_dir() . "/checklogs/check-{$providerCode}";
	if(!empty($confNo))
		$mask .= "-$confNo";
	$mask .= "-*";
	$logs = glob($mask);
	return $logs;
}

function getAccountLogsFromS3($fields){
	// autoloading
	getSymfonyContainer();

	$client = getSymfonyContainer()->get(S3Client::class);
	$bucket = getSymfonyContainer()->getParameter('aws_s3_bucket');

	$iterator = $client->getIterator('ListObjects', array('Bucket' => $bucket, 'Prefix' => "{$fields['partner']}_{$fields['provider']}_{$fields['method']}"));

	foreach ($iterator as $object) {
		if(!preg_match("/^{$fields['partner']}_{$fields['provider']}_{$fields['method']}_{$fields['id']}\.zip$/i", $object['Key']))
			continue;

		$filename = sys_get_temp_dir() . "/checklogs/restapi_".$object['Key'];
		if(!file_exists($filename)){
			$dir = dirname($filename);
			if(!file_exists($dir))
				mkdir($dir, 0777, true);
			$result = $client->getObject(array(
				'Bucket' => $bucket,
				'Key' => $object['Key'],
				'SaveAs' => $filename
			));
			touch($filename, strtotime($result['LastModified']));
		}
	}
}


<?php
require __DIR__ . '/../web/kernel/public.php';

$opts = getopt("a:f:r");

$accountId = intval($opts['a']);
$file = $opts['f'];

if(isset($opts['r'])){
	echo "dumping report to $file\n";
	$options = CommonCheckAccountFactory::getDefaultOptions();
	$options->dumpReport = $file;
	$options->checkIts = true;
	CommonCheckAccountFactory::checkAndSave($accountId, $options);
	if(!file_exists($file))
		die("failed to record, timeout\n");
	echo "recorded\n";
}
else {
	echo "saving account $accountId from $file\n";
	$report = unserialize(file_get_contents($file));
	$options = new AuditorOptions();
	$options->checkIts = true;
	CommonCheckAccountFactory::manuallySave($accountId, $report, $options);
	echo "saved\n";
}


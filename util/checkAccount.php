<?php
require "../web/kernel/public.php";
set_time_limit(3600 * 24);
require_once '../web/account/common.php';
do{
	$nAccountID = intval(trim(fgets(STDIN)));
	if($nAccountID <= 0)
		die("invalid account id: $nAccountID\n");
	try {
		$options = CommonCheckAccountFactory::getDefaultOptions();
		CommonCheckAccountFactory::checkAndSave($nAccountID, $options);
		$result = true;
	} catch (Exception $e) {
		DieTrace($e->getMessage(), false);
		$result = false;
	}
	if($result)
		echo "account $nAccountID: success\n";
	else
		echo "account $nAccountID: error\n";
	if(memory_get_usage(true) > 60000000){
		echo "memory limit hit! exiting\n";
		break;
	}
}while(true);
?>

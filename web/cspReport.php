<?
$report = file_get_contents('php://input');
if(isset($_SERVER['HTTP_USER_AGENT']))
	$report .= "\n" . $_SERVER['HTTP_USER_AGENT'];
file_put_contents('/var/log/www/awardwallet/csp.log', date("Y-m-d H:i:s ") . $report . "\n", FILE_APPEND);
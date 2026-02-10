#!/usr/bin/php
<?
error_reporting(E_ALL);
require __DIR__.'/amazonLib.php';

$time = getdate();
if(($time['hours'] == 11 && $time['minutes'] > 45) || ($time['hours'] == 12 && $time['minutes'] < 5)){
	echo "cron critical time (12:00), dailyJobs, is too near, skipping update\n";
	exit(1);
}
echo "getting balancer state\n";
$instances = getInstances();
var_export($instances);
echo "\n";
if(count($instances) < 2)
	exitWithError("expected at least 2 instances, something wrong, check amazon console");
$expectedInstances = count($instances);
checkOnline(600);

echo "applying migrations\n";
execPassthru("umask 0002;export TERM=xterm;export HOME=/home/veresch;svn update /www/awardwallet/app/Migrations || exit 3; php /www/awardwallet/app/console doctrine:migrations:migrate --no-interaction");

foreach($instances as $instance => $state){
	$server = getInstanceInfo($instance);
	echo "updating instance $instance, ip: {$server['ip']}, name: {$server['name']}\n";

	checkStatus($server['name']);
	execCommand("./elb-deregister-instances-from-lb Frontend --instances $instance");
	echo "removed from balancer, sleeping 30 sec\n";

	execPassthru("ssh -o 'StrictHostKeyChecking no' -o 'CheckHostIP no' -i /home/veresch/.ssh/alexiPassLess.key veresch@{$server['ip']} ".
	"'umask 0002;export TERM=xterm;sleep 30;".
	"cd /www/awardwallet || exit 1;".
	"/www/awardwallet/util/sync/updateInstance.sh || exit 2'");
//	"cd /www/awbeta || exit 1;".
//	"/www/awardwallet/util/sync/updateInstance.sh || exit 2'");

	execCommand("./elb-register-instances-with-lb Frontend --instances $instance");
	echo "added to balancer, sleeping 30 sec\n";
	sleep(30);

	checkStatus($server['name']);
	$curInstances = getInstances();
	if(count($curInstances) != $expectedInstances)
		exitWithError("expected $expectedInstances instances, something wrong");

	checkOnline(600);
}

echo "done\n";


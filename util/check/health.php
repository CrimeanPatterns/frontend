#!/usr/bin/php
<?
require __DIR__."/../../web/kernel/public.php";
require_once __DIR__."/../../web/lib/classes/SmsSender.php";

chdir(__DIR__."/../../");

$opts = getopt("t");

$failures = [];
$output = [];

if(isset($opts['t'])){
	$exitCode = 1;
	$output = array("test mode, simulating error");
	$failures[] = "Test";
}
else {
	echo "checking wsdl health\n";
	exec("phpunit tests/wsdl/WsdlTest.php", $out, $exitCode);
	if($exitCode != 0) {
		$failures[] = 'Wsdl';
		$output = array_merge($output, $out);
	}
}

// check logs
$tests = [
	[
		'Name' => 'Security',
		'Query' => '{query: {bool: {must: [{match: {"level": "WARNING"}}, {match: {channel: "security"}}, {range: {"@timestamp": {gte: "now-10m"}}}]}}}',
		'Min' => 1,
		'Max' => 800,
	]
];
foreach($tests as $test){
	echo "testing {$test['Name']}\n";
	$response = json_decode($rawResponse = curlRequest("http://log.awardwallet.com:9200/_count?pretty=true", 90, [CURLOPT_POSTFIELDS => $test['Query']]), true);
	if(is_array($response) && isset($response['count']))
		$count = $response['count'];
	else
		$count = 0;
	if($count < $test['Min'] || $count > $test['Max']){
		$failures[] = $test['Name'];
		$output[] = "Test {$test['Name']} failed, test definition: ";
		$output[] = json_encode($test, JSON_PRETTY_PRINT);
		$output[] = "Received: ";
		$output[] = $rawResponse;
	}
}

echo implode("\n", $output);
echo "\n";

if(!empty($failures)){
	if(DBUtils::createExpirableParam('health check failure', 200)){
		echo "ignoring single failure\n";
	}
	else{
		echo "failure, mailing\n";
		mail(ConfigValue(CONFIG_ERROR_EMAIL), "ALERT: Health check failed: " . implode(", ", $failures) , implode("\n", $output), EMAIL_HEADERS);
		$sender = new SmsSender();
		$sender->send("Health check failed: " . implode(", ", $failures));
	}
}

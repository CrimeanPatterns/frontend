<?php

$schema = "historyFields";
require "start.php";
drawHeader("History Fields");

$stat = array();
$count = 0;

$q = new TQuery("SELECT Code FROM Provider WHERE CanCheckHistory = 1 ORDER BY Code");
while (!$q->EOF) {

	$code = $q->Fields['Code'];
	$checker = getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\CheckerFactory::class)->getAccountChecker($code, true);
	$columns = $checker->GetHistoryColumns();

	if (is_array($columns)) {
		foreach ($columns as $key) {
			array_key_exists($key, $stat) ? $stat[$key]++ : $stat[$key] = 1;
		}
		$columns = print_r($columns, true);
		$columns = ltrim($columns, 'Array');
		$columns = trim($columns);
		$columns = trim($columns, '()');
	} else {
		$columns = 'Wrong history columns!?';
	}

	print "<div><b>$code</b><br><pre style='margin-top: 0;'>$columns</pre></div>";
	$count++;

	$q->Next();
}

print "<h2>Code Count</h2>";
print "Total programs: $count<pre>".print_r($stat, true)."</pre>";

print "<br>";
drawFooter();

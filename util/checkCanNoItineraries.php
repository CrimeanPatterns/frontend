<?php

require_once __DIR__.'/../web/kernel/public.php';
set_time_limit(300);

$canCheck = array();
$count = 0;

$template = "UPDATE Provider SET CanCheckNoItineraries = 1 WHERE Code = '%s';";

print "checking";
$path = __DIR__.'/../web/engine';
$dir = dir($path);
while (false !== ($entry = $dir->read())) {
	if (file_exists("$path/$entry/functions.php")) {
		$count += 1;
		$file = file_get_contents("$path/$entry/functions.php");
		if (strpos($file, '$this->noItinerariesArr()') !== false) {
			$canCheck[] = $entry;
		}
	}
	print ".";
}
$dir->close();

sort($canCheck);

print "\nprograms checked: $count\n";
print "can check no itineraries (".count($canCheck)."):\n";
foreach ($canCheck as $name) {
	print sprintf($template, $name)."\n";
}

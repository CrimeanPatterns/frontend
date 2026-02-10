#!/usr/bin/php
<?
require __DIR__."/../web/kernel/public.php";
$options = getopt("f");

echo "importing flights data..\n";
chdir(__DIR__.'/../data');
if(file_exists('flights2.zip'))
	$oldTime = filemtime('flights2.zip');
else
	$oldTime = 0;
passthru('wget -N http://www.libhomeradar.org/downloads/flights2.zip', $retCode);
if($retCode != 0)
	die("wget failed\n");
if(!isset($options['f']) && ($oldTime == filemtime('flights2.zip'))){
	echo "file was not changed\n";
	exit(0);
}
if(file_exists('flights2.txt'))
	if(!unlink('flights2.txt'))
		die("failed to remove old file flights2.txt\n");
passthru('unzip flights2.zip', $retCode);
if($retCode != 0)
	die("unzip failed\n");
if(!file_exists('flights2.txt'))
	die("missing file afer unizip\n");

echo "reading..\n";
$f = fopen("flights2.txt", "r");
if($f === false)
	die("failed to read file\n");
$total = 0;
$startTime = time();
$exist = SQLToArray("select FlightNumber, concat(DepCode, '-', ArrCode) as Route from FlightRoute", "FlightNumber", "Route");
echo "loaded " .  count($exist) . " records\n";
$changed = 0;
$numbers = [];
while(!feof($f)){
	$s = fgets($f);
	$fields = explode("\t", $s);
	if(count($fields) == 8){
		if(($fields['2'] != '') && (strlen($fields['4']) == 3) && (strlen($fields['6']) == 3) && !isset($numbers[$fields[2]])){
			if(!isset($exist[$fields[2]]) || $exist[$fields[2]] != ($fields[4] . '-' . $fields[6])) {
				echo "{$fields[2]} - {$fields[4]} - {$fields[6]}\n";
				$Connection->Execute("insert into FlightRoute(FlightNumber, DepCode, ArrCode)
				values(" . mysql_quote($fields[2]) . ", " . mysql_quote($fields[4]) . ", " . mysql_quote($fields[6]) . ")
				on duplicate key update DepCode = " . mysql_quote($fields[4]) . ", ArrCode = " . mysql_quote($fields[6]));
				$changed++;
			}
			$total++;
			if(($total % 1000) == 0)
				echo "$total records\n";
			$numbers[$fields[2]] = true;
		}
	}
}
fclose($f);
echo "done, $total records, changed: $changed, ".(time() - $startTime)." seconds\n";

?>

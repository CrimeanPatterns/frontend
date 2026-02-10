<?
require __DIR__.'/../../web/kernel/public.php';

$opts = getopt('hi:');

if(isset($opts['h']) || !isset($opts['i']))
	die("this script will search matching ip by pinging provider sites
usage:
	".basename(__FILE__).' -i <ip>'."\n");

$ip = $opts['i'];
echo "searching $ip\n";

$q = new TQuery("select ProviderID, Code, Site from Provider");
$matches = array();
$hosts = array();
while(!$q->EOF){
	echo $q->Fields['Code']." - ".$q->Fields['Site']." - ";
	$url = parse_url(strtolower($q->Fields['Site']));
	if(isset($url['host'])){
		checkHost($url['host'], $ip, $q->Fields['Code'], $hosts, $matches);
	}
	else
		echo "bad url\n";
	$engine = $sPath."/engine/".strtolower($q->Fields['Code'])."/functions.php";
	if(file_exists($engine)){
		$engine = file_get_contents($engine);
		if(preg_match_all("/['\"]http(s)?:\/\/([^'\"\/]+)['\"]/ims", strtolower($engine), $matches, PREG_SET_ORDER)){
			foreach($matches as $match){
				if(!in_array($match[2], $hosts)){
					echo $q->Fields['Code'].' - ';
					checkHost($match[2], $ip, $q->Fields['Code'], $hosts, $matches);
				}
			}
		}
	}
	$q->Next();
}

function checkHost($host, $ip, $code, &$hosts, &$matches){
	$hosts[] = $host;
	echo $host.' - ';
	$hostIp = gethostbyname($host);
	echo $hostIp.' - ';
	if(strpos($hostIp, $ip) === 0){
		echo "MATCH!\n";
		$matches[] = $code;
	}
	else
		echo "miss\n";
}

echo "done, matches: ".join(", ", $matches)."\n";

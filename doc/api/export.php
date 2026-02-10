#!/usr/bin/env php
<?php

umask(0000);
set_time_limit(0);

//test php version
if (version_compare(PHP_VERSION, '5.1.0', '<')) {
	echo "PHP 5.1 required\n";
	exit(2);
}
//test extensions exists
if (!function_exists('curl_version')) {
	echo "Curl extension required\n";
	exit(2);
}
if (!function_exists('json_encode')) {
	echo "Json extension required\n";
	exit(2);
}

$arguments = $argv;
array_shift($arguments);

$params = getParams($arguments);
$secret = $params['secret'];
$request = $params['request'];
$host = $params['host'];
$auth = $params['auth'];
$pretty = $params['pretty'];
$outputFile = $params['outputFile'];

if (empty($secret) || empty($request)) {
	printHelp();
	exit(0);
}

if ($request !== 'all') {
	$data = request($host, $request, $secret, $auth);
	if ($data !== false) {
		save($data, $outputFile, $pretty);
		exit(0);
	}
} else {
	$parts = explode('.', $outputFile);
	if (count($parts) > 1) {
		$ext = '.' . array_pop($parts);
		$fileNamePrefix = implode('.', $parts) . '.';
	} else {
		$ext = '';
		$fileNamePrefix = $outputFile . '.';
	}

	$requests = [
		[
			'request' => 'member',
			'key' => 'members',
			'id' => 'memberId'
		],
		[
			'request' => 'connectedUser',
			'key' => 'connectedUsers',
			'id' => 'userId'
		],
	];
	$errors = [];
	foreach ($requests as $r) {
		echo $r['request'] . ':';
		$persons = request($host, $r['request'], $secret, $auth);
		if ($persons !== false) {
			save($persons, $fileNamePrefix . $r['request'] . $ext, $pretty);
			$persons = json_decode($persons, true);
			if (isset($persons[$r['key']])) {
				foreach ($persons[$r['key']] as $p) {
					if (isset($p[$r['id']])) {
						echo '!';
						$person = request($host, $r['request'] . '/' . $p[$r['id']], $secret, $auth);
						if ($person !== false) save($person, $fileNamePrefix . $r['request'] . $p[$r['id']] . $ext, $pretty);
						else $errors[] = $r['request'] . '/' . $p[$r['id']];
					}
					if (isset($p['accountsIndex'])) {
						foreach ($p['accountsIndex'] as $a) {
							echo '.';
							$account = request($host, 'account' . '/' . $a['accountId'], $secret, $auth);
							if ($account !== false) save($account, $fileNamePrefix . 'account' . $a['accountId'] . $ext, $pretty);
							else $errors[] = 'account' . '/' . $a['accountId'];
						}
					}
				}
			}
		} else {
			$errors[] = $r['request'];
		}
		echo "\n";
	}
	if (count($errors)) {
		echo "requests with error:\n";
		foreach ($errors as $error) echo "- " . $error . "\n";
	}
	exit(0);
}
exit(1);

// ==============================

function save($data, $outputFile, $pretty = false) {
	if ($pretty) $data = json_encode(json_decode($data), JSON_PRETTY_PRINT);
	file_put_contents($outputFile, $data);
}

function request($host, $requestUrl, $apiKey, $auth = null) {
	$requestUrl = trim($requestUrl, '/');
	$requestUrl = $host . '/api/export/v1/' . $requestUrl;
	$conn = curl_init();
	curl_setopt($conn, CURLOPT_HEADER, false);
	curl_setopt($conn, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($conn, CURLOPT_FAILONERROR, false);
	curl_setopt($conn, CURLOPT_TIMEOUT, 10*60);
	curl_setopt($conn, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($conn, CURLOPT_BINARYTRANSFER, true);
	curl_setopt($conn, CURLOPT_URL, $requestUrl);
	curl_setopt($conn, CURLOPT_POST, false);
	curl_setopt($conn, CURLOPT_HTTPHEADER, ['X-Authentication: ' . $apiKey]);
	if ($auth) {
		curl_setopt($conn, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($conn, CURLOPT_USERPWD, $auth);
	}
	$response = curl_exec($conn);
	if ($response === false) {
		echo "CURL error: " . curl_error($conn) . "\n";
	}
	$responseCode = curl_getinfo($conn, CURLINFO_HTTP_CODE);
	if ($responseCode !== 200) {
		echo "Error. Server response code: " . $responseCode . "\n";
		$response = false;
	}
	return $response;
}

function getParams($arguments) {
	$params = array(
		'secret'     => null,
		'request'    => null,
		'auth'       => null,
		'pretty'     => false,
		'host'       => 'https://business.awardwallet.com',
		'outputFile' => 'data.json',
	);

	$options = array(
		'-s'       => 'secret',
		'--secret' => 'secret',
		'-r'       => 'request',
		'--request' => 'request',
		'-p'       => 'pretty',
		'--pretty' => 'pretty',
		'-h'       => 'host',
		'--host'   => 'host',
		'-a'       => 'auth',
		'--auth'   => 'auth',
		'-o'       => 'outputFile',
		'--output' => 'outputFile',
	);

	while (count($arguments)) {
		$arg = array_shift($arguments);
		if (strpos($arg, '--') === 0) {
			if (strpos($arg, '=') !== false) {
				list($arg, $val) = explode('=', $arg, 2);
				if (array_key_exists($arg, $options)) {
					$val = trim($val, '"');
					$params[$options[$arg]] = $val;
				}
			} else {
				if (array_key_exists($arg, $options)) {
					$params[$options[$arg]] = 1;
				}
			}
		} elseif (strpos($arg, '-') === 0) {
			if (array_key_exists($arg, $options)) {
				if (count($arguments)) {
				$val = current($arguments);
				if (strpos($val, '-') !== 0) {
					$val = trim($val, '"');
					$params[$options[$arg]] = $val;
					array_shift($arguments);
				} else {
					$params[$options[$arg]] = 1;
				}
				} else {
					$params[$options[$arg]] = 1;
				}
			}
		}
	}

	return $params;
}

function printHelp() {
	$helpText = "AwardWallet Business Export API utility\n";
	$helpText .= "Usage: export -s <secret> -r <request> [-o <output filename>]\n";
	$helpText .= "Options:\n";
	$helpText .= "    -s <secret>\n";
	$helpText .= "    --secret=<secret>      API Key from https://business.awardwallet.com/profile/api\n";
	$helpText .= "    -r <request>\n";
	$helpText .= "    --request=<request>    request \n";
	$helpText .= "                           see docs in export.yml\n";
	$helpText .= "    -o <filename>\n";
	$helpText .= "    --output=<filename>    output filename\n";
	$helpText .= "                           default \"data.json\"\n";
	$helpText .= "\n";
	echo $helpText;
}

?>
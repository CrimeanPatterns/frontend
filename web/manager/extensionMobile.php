<?php

$schema = "extensionMobile";
require "start.php";
drawHeader("Extension Mobile support info");

print "<table border=1>";
print "<tr><th>Provider</th><th>Mobile FlightStatus</th><th>Mobile Autologin</th><th>Desktop Autologin</th></tr>";

$files = glob("$sPath/../engine/*/extensionMobile.js");
$providers = [];
foreach ($files as $filename) {
	$providers[basename(dirname($filename))] = $filename;
}
/** @var \Doctrine\DBAL\Connection $connection */
$connection = getSymfonyContainer()->get('doctrine')->getConnection();
$stmt = $connection->executeQuery("
		SELECT
			Code,
			Accounts,
			AutoLogin
		FROM Provider
		WHERE
			Code IN (?)
		ORDER BY
			Accounts DESC
	",
	[array_keys($providers)],
	[\Doctrine\DBAL\Connection::PARAM_STR_ARRAY]
);
$providersView = [];
foreach ($stmt->fetchAll() as $providerStat) {
	if (!isset($providers[$providerStat['Code']])) {
		DieTrace('Missing provider' . $providerStat['Code']);
	}
	$provider = $providerStat['Code'];
	$filename = $providers[$provider];
	$content = file_get_contents($filename);
	$flightStatus = preg_match('/flightStatus:/U', $content) ? '&#x2713;' : '';
	$autologin = preg_match('/autologin:/U', $content) ? '&#x2713;' : '';
	$desktop = in_array($providerStat['AutoLogin'], [AUTOLOGIN_EXTENSION, AUTOLOGIN_MIXED]) ? '&#x2713;' : '';
	if (!empty($autologin)) {
		$desktop = "<span style='color: lightgrey' title='Using mobile'>$desktop</span>";
	}
	print "<tr><td><a href='/manager/list.php?Code=$provider&Schema=Provider'>$provider</a></td><td align=center>$flightStatus</td><td align=center>$autologin</td><td align=center>$desktop</td></tr>";
}

print "</table>";
print "<br>";
drawFooter();

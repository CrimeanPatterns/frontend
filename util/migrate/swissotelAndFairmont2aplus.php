#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "merging swissotel/fairmont and aplus\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$swissotelId = Lookup("Provider", "Code", "ProviderID", "'swissotel'", true);
$fairmontId = Lookup("Provider", "Code", "ProviderID", "'fairmont'", true);
$aplusId = Lookup("Provider", "Code", "ProviderID", "'aplus'", true);

$sql = "select AccountID, Login, UserID, UserAgentID from Account where ProviderID = {$swissotelId}";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading...\n";
$q = new TQuery($sql);
$swissotelMigrated = 0;
while (!$q->EOF) {
    if (processMigrate('swissotel', $q->Fields, $aplusId, isset($opts['f'])))
        $swissotelMigrated++;
    $q->Next();
}
$totalSwissotel = $q->Position;


$sql = "select AccountID, Login, UserID, UserAgentID from Account where ProviderID = {$fairmontId}";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading...\n";
$q = new TQuery($sql);
$fairmontMigrated = 0;
while (!$q->EOF) {
    if (processMigrate('fairmont', $q->Fields, $aplusId, isset($opts['f'])))
        $fairmontMigrated++;
    $q->Next();
}
$totalFairmont = $q->Position;

echo "done, processed {$totalSwissotel} swissotel accounts, switched to aplus: {$swissotelMigrated}\n";
echo "done, processed {$totalFairmont} fairmont accounts, switched to aplus: {$fairmontMigrated}\n";

function processMigrate($code, $fields, $aplusId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, {$code} login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $aplusId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']) {
            echo ", found aplus with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("update Account set ProviderID = $aplusId where AccountID = {$fields['AccountID']}");
        }
    }
    else {
        echo ", skip\n";
    }

    return $migrate;
}

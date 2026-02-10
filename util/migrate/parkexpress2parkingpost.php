#!/usr/bin/php
<?php

require __DIR__ . "/../../web/kernel/public.php";

echo "merging parkexpress and parkingspot\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$parkexpressId = Lookup("Provider", "Code", "ProviderID", "'parkexpress'", true);
$parkingspotId = Lookup("Provider", "Code", "ProviderID", "'parkingspot'", true);

$sql = "select AccountID, Login, UserID, UserAgentID from Account where ProviderID = {$parkexpressId}";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading...\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processParkexpress($q->Fields, $parkingspotId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to parkingspot: {$migrated}\n";

function processParkexpress($fields, $parkingspotId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, parkexpress login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $parkingspotId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']) {
            echo ", found parkingspot account with same login";
            $migrate = false;
            break;
        }
        if (!strstr($fields['Login'], '@')) {
            echo ", account with wrong login, email needed";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("update Account set ProviderID = {$parkingspotId} where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else {
        echo ", skip\n";
    }

    return $migrate;
}

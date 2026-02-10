#!/usr/bin/php
<?php

require __DIR__ . "/../../web/kernel/public.php";

echo "merging swisscorporate and plusawards\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$swisscorporateId = Lookup("Provider", "Code", "ProviderID", "'swisscorporate'", true);
$plusawardsId = Lookup("Provider", "Code", "ProviderID", "'plusawards'", true);

$sql = "select AccountID, Login, UserID, UserAgentID from Account where ProviderID = {$swisscorporateId}";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "\nloading...\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processIdine($q->Fields, $plusawardsId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to plusawardsId: {$migrated}\n";

function processIdine($fields, $plusawardsId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, swisscorporate login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $plusawardsId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']) {
            echo ", found plusawards with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("update Account set ProviderID = $plusawardsId where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else {
        echo ", skip\n";
    }

    return $migrate;
}

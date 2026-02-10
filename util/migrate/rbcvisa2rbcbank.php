#!/usr/bin/php
<?php

require __DIR__ . "/../../web/kernel/public.php";

echo "merging rbcvisa and rbcbank\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$rbcvisaId = Lookup("Provider", "Code", "ProviderID", "'rbcvisa'", true);
$rbcbankId = Lookup("Provider", "Code", "ProviderID", "'rbcbank'", true);

$sql = "select AccountID, Login, Login2, UserID, UserAgentID from Account where ProviderID = {$rbcvisaId}";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading...\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processRbcvisa($q->Fields, $rbcbankId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to rbcbank: {$migrated}\n";

function processRbcvisa($fields, $rbcbankId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, rbcvisa login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $rbcbankId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']) {
            echo ", found rbcbank with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("update Account set ProviderID = $rbcbankId, Login2 = 'CanadaRBCRewards', Login3 = '{$fields['Login2']}' where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else {
        echo ", skip\n";
    }

    return $migrate;
}

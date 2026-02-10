#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "merging spg and marriott\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$spgId = Lookup("Provider", "Code", "ProviderID", "'spg'", true);
$marriottId = Lookup("Provider", "Code", "ProviderID", "'marriott'", true);

$sql = "select AccountID, Login, Login2, UserID, UserAgentID from Account where ProviderID = {$spgId}";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading...\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processSpg($q->Fields, $marriottId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to marriott: {$migrated}\n";

function processSpg($fields, $marriottId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, spg login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $marriottId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']
            && $q->Fields['Login2'] == $fields['Login2']) {
            echo ", found marriott account with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        if (filter_var($fields['Login'], FILTER_VALIDATE_EMAIL) === false) {
            echo " login2 spg";
            $login2 = "'spg'";
        }
        else {
            $login2 = "'marriott'";
            echo " login2 marriott";
        }
        if ($force) {
            $Connection->Execute("update Account set ProviderID = $marriottId, Login2 = {$login2} where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
        echo ", migrating\n";
    }
    else {
        echo ", skip\n";
    }

    return $migrate;
}

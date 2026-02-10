#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "merging mbpass and stationcasino\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$mbpassId = Lookup("Provider", "Code", "ProviderID", "'mbpass'", true);
$stationcasinoId = Lookup("Provider", "Code", "ProviderID", "'stationcasino'", true);

$sql = "select AccountID, Login, UserID, UserAgentID from Account where ProviderID = {$mbpassId}";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading...\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processmbpass($q->Fields, $stationcasinoId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to stationcasino: {$migrated}\n";

function processmbpass($fields, $stationcasinoId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, mbpass login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $stationcasinoId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']) {
            echo ", found stationcasino account with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("update Account set ProviderID = $stationcasinoId where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else {
        echo ", skip\n";
    }

    return $migrate;
}

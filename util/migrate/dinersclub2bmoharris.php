#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "merging dinersclub and bmoharris\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$dinersclubId = Lookup("Provider", "Code", "ProviderID", "'dinersclub'", true);
$bmoharrisId = Lookup("Provider", "Code", "ProviderID", "'bmoharris'", true);

$sql = "select AccountID, Login, Login2, UserID, UserAgentID from Account where Login2 = 'int' and ProviderID = {$dinersclubId}";

if (isset($opts['u'])) {
    $sql .= " and UserID = ".intval($opts['u']);
}

echo "loading...\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processDinersclub($q->Fields, $bmoharrisId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to bmoharris: {$migrated}\n";

function processDinersclub($fields, $bmoharrisId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, dinersclub login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $bmoharrisId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']) {
            echo ", found bmoharris account with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    $result = ", skip\n";
    if ($migrate) {
        if ($force) {
            $Connection->Execute("update Account set ProviderID = $bmoharrisId where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
        $result = ", migrating\n";
    }

    echo $result;

    return $migrate;
}

#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "merging borgata and mirage\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$borgataId = Lookup("Provider", "Code", "ProviderID", "'borgata'", true);
$mirageId = Lookup("Provider", "Code", "ProviderID", "'mirage'", true);

$sql = "select AccountID, Login, UserID, UserAgentID from Account where ProviderID = {$borgataId}";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading...\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processBorgata($q->Fields, $mirageId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to mirage: {$migrated}\n";

function processBorgata($fields, $mirageId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, borgata login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $mirageId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']) {
            echo ", found mirage with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("update Account set ProviderID = $mirageId where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else
        echo ", skip\n";

    return $migrate;
}

#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "merging multiplus and lanpass\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$multiplusId = Lookup("Provider", "Code", "ProviderID", "'multiplus'", true);
$lanpassId = Lookup("Provider", "Code", "ProviderID", "'lanpass'", true);

$sql = "select AccountID, Login, UserID, UserAgentID from Account where ProviderID = {$multiplusId}";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading..\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processLanpass($q->Fields, $lanpassId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to lanpass: {$migrated}\n";

function processLanpass($fields, $lanpassId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, multiplus login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $lanpassId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']) {
            echo ", found lanpass account with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("update Account set ProviderID = $lanpassId where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else {
        echo ", skip\n";
    }

    return $migrate;
}

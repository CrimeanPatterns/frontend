#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "merging snow and vail\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$snowId = Lookup("Provider", "Code", "ProviderID", "'snow'", true);
$vailId = Lookup("Provider", "Code", "ProviderID", "'vail'", true);

$sql = "select AccountID, Login, UserID, UserAgentID from Account where ProviderID = {$snowId}";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading..\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processsnow($q->Fields, $vailId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to vail: {$migrated}\n";

function processsnow($fields, $vailId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, snow login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $vailId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']) {
            echo ", found vail with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("update Account set ProviderID = $vailId where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else
        echo ", skip\n";

    return $migrate;
}

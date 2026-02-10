#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "ural changed\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$uralId = Lookup("Provider", "Code", "ProviderID", "'ural'", true);

$sql = "select AccountID, Login, Login2, UserID from Account where ProviderID = $uralId";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading..\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processUral($q->Fields, $uralId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, changed: {$migrated}\n";

function processUral($fields, $uralId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, ural login " . $fields['Login'] . ", ural login2 " . $fields['Login2'];
    $migrate = true;
    if (empty($fields['Login2'])) {
        echo ", found ural with empty login2";
        $migrate = false;
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("update Account set login = '".$fields['Login2']."' where AccountID = {$fields['AccountID']} and ProviderID = {$uralId}");
            $Connection->Execute("update Account set login2 = null where AccountID = {$fields['AccountID']} and ProviderID = {$uralId}");
        }
    } else {
        echo ", skip\n";
    }

    return $migrate;
}

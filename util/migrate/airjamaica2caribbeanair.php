#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "merging airjamaica and caribbeanair\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$airjamaicaId = Lookup("Provider", "Code", "ProviderID", "'airjamaica'", true);
$caribbeanairId = Lookup("Provider", "Code", "ProviderID", "'caribbeanair'", true);

$sql = "select AccountID, Login, UserID, UserAgentID from Account where ProviderID = {$airjamaicaId}";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading...\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processAirjamaica($q->Fields, $caribbeanairId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to caribbeanair: {$migrated}\n";

function processAirjamaica($fields, $caribbeanairId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, airjamaica login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $caribbeanairId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']) {
            echo ", found caribbeanair account with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("update Account set ProviderID = $caribbeanairId where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else {
        echo ", skip\n";
    }

    return $migrate;
}

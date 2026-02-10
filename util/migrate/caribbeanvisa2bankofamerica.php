#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "merging caribbeanvisa and bankofamerica\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$caribbeanvisaId = Lookup("Provider", "Code", "ProviderID", "'caribbeanvisa'", true);
$bankofamericaId = Lookup("Provider", "Code", "ProviderID", "'bankofamerica'", true);

$sql = "SELECT AccountID, Login, UserID, UserAgentID FROM Account WHERE ProviderID = $caribbeanvisaId";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading..\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processCaribbeanvisa($q->Fields, $bankofamericaId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to bankofamerica (WorldPoints): {$migrated}\n";

function processCaribbeanvisa($fields, $bankofamericaId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, caribbeanvisa login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select AccountID, Login from Account
	where ProviderID = $bankofamericaId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']) {
            echo ", found bankofamerica with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("UPDATE Account SET ProviderID = $bankofamericaId WHERE AccountID = {$fields['AccountID']}");
            $Connection->Execute("DELETE FROM AccountProperty WHERE AccountID = {$fields['AccountID']}");
        }
    } else {
        echo ", skip\n";
    }

    return $migrate;
}

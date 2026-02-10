#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "merging citirewards and citybank\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$citirewardsId = Lookup("Provider", "Code", "ProviderID", "'citirewards'", true);
$citybankId = Lookup("Provider", "Code", "ProviderID", "'citybank'", true);

$sql = "SELECT AccountID, Login, UserID, UserAgentID FROM Account WHERE ProviderID = $citirewardsId";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading..\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processCitirewards($q->Fields, $citybankId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to citybank: {$migrated}\n";

function processCitirewards($fields, $citybankId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, citirewards login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select AccountID, Login from Account
	where ProviderID = $citybankId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']) {
            echo ", found citybank with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("UPDATE Account SET ProviderID = $citybankId, Login2 = 'Australia' WHERE AccountID = {$fields['AccountID']}");
            $Connection->Execute("DELETE FROM AccountProperty WHERE AccountID = {$fields['AccountID']}");
        }
    } else {
        echo ", skip\n";
    }

    return $migrate;
}

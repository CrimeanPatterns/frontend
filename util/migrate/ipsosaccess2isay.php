#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "merging ipsosaccess and isay\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$ipsosaccessId = Lookup("Provider", "Code", "ProviderID", "'ipsosaccess'", true);
$isayId = Lookup("Provider", "Code", "ProviderID", "'isay'", true);

$sql = "select AccountID, Login, UserID, UserAgentID from Account where ProviderID = {$ipsosaccessId}";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading..\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processIpsosaccess($q->Fields, $isayId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to isay: {$migrated}\n";

function processIpsosaccess($fields, $isayId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, ipsosaccess login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $isayId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']
            && $q->Fields['Login2'] == 'en-US') {
            echo ", found isay account with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("update Account set ProviderID = $isayId, Login2 = 'en-US' where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else {
        echo ", skip\n";
    }

    return $migrate;
}

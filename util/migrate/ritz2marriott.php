#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "merging ritz and marriott\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$ritzId = Lookup("Provider", "Code", "ProviderID", "'ritz'", true);
$marriottId = Lookup("Provider", "Code", "ProviderID", "'marriott'", true);

// update marriott accounts
processMarriott($marriottId, isset($opts['f']));

$sql = "select AccountID, Login, UserID, UserAgentID from Account where ProviderID = {$ritzId}";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading..\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processRitz($q->Fields, $marriottId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to marriott: {$migrated}\n";

function processMarriott($marriottId, $force) {
    global $Connection;
    echo "\nSet Login2 = 'marriott' for marriott accounts";
    $q = new TQuery("select count(*) as countAccounts from Account where ProviderID = {$marriottId}");
    if (!$q->EOF && isset($q->Fields['countAccounts'])) {
        echo "\nTotal {$q->Fields['countAccounts']} marriott accounts were found";
    }
    if ($force)
        $Connection->Execute("update Account set Login2 = 'marriott' where ProviderID = {$marriottId}");
    else
        echo "\njust test";
}

function processRitz($fields, $marriottId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, ritz login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $marriottId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']) {
            echo ", found marriott with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("update Account set ProviderID = $marriottId, Login2 = 'ritz' where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else {
        echo ", skip\n";
    }

    return $migrate;
}

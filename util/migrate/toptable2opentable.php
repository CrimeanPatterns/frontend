#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "merging toptable and opentable\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$toptableId = Lookup("Provider", "Code", "ProviderID", "'toptable'", true);
$opentableId = Lookup("Provider", "Code", "ProviderID", "'opentable'", true);

// update opentable accounts
processOpentable($opentableId, isset($opts['f']));

$sql = "select AccountID, Login, Login2, UserID, UserAgentID from Account where ProviderID = {$toptableId}";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading..\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processToptable($q->Fields, $opentableId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to opentable: {$migrated}\n";

function processOpentable($opentableId, $force) {
    global $Connection;
    echo "\nSet Login2 = 'USA' for opentable accounts";
    $q = new TQuery("select count(*) as countAccounts from Account where ProviderID = {$opentableId}");
    if (!$q->EOF && isset($q->Fields['countAccounts'])) {
        echo "\nTotal {$q->Fields['countAccounts']} opentable accounts were found";
    }
    if ($force)
        $Connection->Execute("update Account set Login2 = 'USA' where ProviderID = {$opentableId}");
    else
        echo "\njust test";
}

function processToptable($fields, $opentableId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, toptable login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $opentableId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']
            && $q->Fields['Login2'] == $fields['Login2']) {
            echo ", found opentable account with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("update Account set ProviderID = $opentableId, Login2 = 'UK' where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else {
        echo ", skip\n";
    }

    return $migrate;
}

#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "merging buycom and ebates\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$buycomId = Lookup("Provider", "Code", "ProviderID", "'buycom'", true);
$ebatesId = Lookup("Provider", "Code", "ProviderID", "'ebates'", true);

$sql = "select AccountID, Login, Login2, UserID, UserAgentID from Account where ProviderID = {$buycomId}";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading...\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processBuycom($q->Fields, $ebatesId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to ebates: {$migrated}\n";

function processBuycom($fields, $ebatesId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, buycom login: " . $fields['Login'] . " / login2: '" . $fields['Login2'] . "'";
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $ebatesId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if (
            $q->Fields['Login'] == $fields['Login']
            && $q->Fields['Login2'] == 'USA'
            && ($fields['Login2'] == 'America' || $fields['Login2'] == '')
        ) {
            echo ", found ebates account with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $login2 = 'USA';

            if ($fields['Login2'] == 'Germany') {
                $login2 = 'Germany';
            }

            $Connection->Execute("update Account set ProviderID = $ebatesId, Login2 = '{$login2}' where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else {
        echo ", skip\n";
    }

    return $migrate;
}

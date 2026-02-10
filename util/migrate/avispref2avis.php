#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "merging avispref and avis\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$avisprefId = Lookup("Provider", "Code", "ProviderID", "'avispref'", true);
$avisId = Lookup("Provider", "Code", "ProviderID", "'avis'", true);

$sql = "select AccountID, Login, UserID, UserAgentID from Account where ProviderID = {$avisprefId}";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading...\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processAvispref($q->Fields, $avisId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to avis: {$migrated}\n";

function processAvispref($fields, $avisId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, avispref login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $avisId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']
            && $q->Fields['Login2'] == 'UK') {
            echo ", found avis account with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("update Account set ProviderID = $avisId, Login2 = 'UK' where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else {
        echo ", skip\n";
    }

    return $migrate;
}

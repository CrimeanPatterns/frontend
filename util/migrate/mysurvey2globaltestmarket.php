#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "merging mysurvey and globaltestmarket\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$mysurveyId = Lookup("Provider", "Code", "ProviderID", "'mysurvey'", true);
$globaltestmarketId = Lookup("Provider", "Code", "ProviderID", "'globaltestmarket'", true);

$sql = "select AccountID, Login, Login2, UserID, UserAgentID from Account where ProviderID = {$mysurveyId}";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading...\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processGlobaltestmarket($q->Fields, $globaltestmarketId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to globaltestmarket: {$migrated}\n";

function processGlobaltestmarket($fields, $globaltestmarketId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, globaltestmarket login '" . $fields['Login']."' / login2 '{$fields['Login2']}'";
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $globaltestmarketId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']
            && $q->Fields['Login2'] == $fields['Login2']) {
            echo ", found globaltestmarket account with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        switch ($fields['Login2']) {
            case 'de':
                $login2 = 'Germany';
                break;
            case 'uk':
                $login2 = 'UK';
                break;
            case 'fr':
                $login2 = 'France';
                break;
            case 'au':
                $login2 = 'Australia';
                break;
            default:
                $login2 = "USA";
                break;
        }
        echo ", set NEW login2 = '{$login2}'";
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("update Account set ProviderID = {$globaltestmarketId}, Login2 = '{$login2}' where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else {
        echo ", skip\n";
    }

    return $migrate;
}

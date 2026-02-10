#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "udrive changed\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$udriveID = Lookup("Provider", "Code", "ProviderID", "'udrive'", true);

$sql = "select AccountID, Login, UserID from Account where ProviderID = $udriveID";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading..\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processUdrive($q->Fields, $udriveID, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, changed: {$migrated}\n";

function processUdrive($fields, $udriveID, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, udrive login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select AccountID from Account
	where ProviderID = $udriveID and Login = '".addslashes($fields['Login'])."' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        $udriveNumber = getAccountNumber($fields['AccountID']);
        if (empty($udriveNumber)) {
            echo ", null";
            $migrate = false;
            break;
        }
        else
            echo ", udrive account number: ".$udriveNumber;
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("update Account set Login = '{$udriveNumber}' where AccountID = {$fields['AccountID']} and ProviderID = {$udriveID}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    } else {
        echo ", skip\n";
    }

    return $migrate;
}

function getAccountNumber($accountId) {
    $q = new TQuery("select
		ap.Val
	from
		AccountProperty ap
		join ProviderProperty pp on ap.ProviderPropertyID = pp.ProviderPropertyID
	where
		ap.AccountID = $accountId
		and pp.Kind = ".PROPERTY_KIND_NUMBER);
    if (!$q->EOF)
        return $q->Fields['Val'];
    else
        return null;
}

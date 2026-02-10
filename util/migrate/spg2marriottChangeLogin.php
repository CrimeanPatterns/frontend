#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "marriott: change login for spg accounts\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$marriottId = Lookup("Provider", "Code", "ProviderID", "'marriott'", true);

$sql = "select AccountID, Login, Login2, UserID, UserAgentID 
        from Account
        where ProviderID = {$marriottId}
        and (Login2 = 'spg' or (Login2 = 'marriott' and ErrorMessage = 'Your old member number and username will be deactivated in early 2019.'))
        and ErrorCode not in (3, 8)";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading...\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processSpgLoginChange($q->Fields, $marriottId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, changed login to account number: {$migrated}\n";

function processSpgLoginChange($fields, $marriottId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, old {$fields['Login2']} login {$fields['Login']}";
    $migrate = true;

    if (filter_var($fields['Login'], FILTER_VALIDATE_EMAIL) !== false) {
        if ($migrate) {
            if ($force) {
                $Connection->Execute("update Account set Login2 = 'marriott' where AccountID = {$fields['AccountID']}");
            }
            echo ", login is email, migrating\n";
        }
        return $migrate;
    }

    $accountNumber = getAccountNumber($fields['AccountID']);
    if (isset($accountNumber) && is_numeric($accountNumber)) {
        echo ", number: ".$accountNumber;
        $sql = "select AccountID, Login from Account where ProviderID = $marriottId and UserID = {$fields['UserID']}";
        $q = new TQuery($sql);
        while (!$q->EOF) {
            if ($accountNumber == $fields['Login']) {
                echo ", found marriott account with same login: {$accountNumber}";
                $migrate = false;
                break;
            }
            $q->Next();
        }
    }
    else {
        $migrate = false;
        if (!isset($accountNumber))
            echo ", number unknown";
        else
            echo ", bad number '{$accountNumber}'";
    }
    if ($migrate) {
        if ($force) {
            $Connection->Execute("update Account set Login = {$accountNumber}, Login2 = 'marriott' where AccountID = {$fields['AccountID']}");
        }
        echo ", set new login {$accountNumber}, migrating\n";
    }
    if (!$migrate)
        echo ", skip\n";

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
    if(!$q->EOF)
        return $q->Fields['Val'];
    else
        return null;
}

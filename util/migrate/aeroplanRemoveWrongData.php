#!/usr/bin/php
<?
require __DIR__."/../../web/kernel/public.php";

echo "aeroplan: remove Wrong Data\n";

$opts = getopt("hfu:l:d");
if (isset($opts['h'])) {
    die("usage
	".basename(__FILE__)." [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u: update scheduled accounts
	-l <limit>  : account limit
	-u <user id>: this user only
	-d: delete trips only
");
}

$aeroplanId = Lookup("Provider", "Code", "ProviderID", "'aeroplan'", true);

$sql = "select
	a.AccountID, a.Login, a.UserID, apn.Val as Number, a.SuccessCheckDate, a.ErrorCode
from
	Account a
	left outer join AccountProperty apn on a.AccountID = apn.AccountID and apn.ProviderPropertyID = 1524
where
	a.ProviderID = {$aeroplanId}";

if (isset($opts['u'])) {
    $sql .= " and a.UserID = ".intval($opts['u']);
}
if (isset($opts['l'])) {
    $sql .= " limit ".intval($opts['l']);
}

echo "loading...\n";

$corrected = 0;

$q = new TQuery($sql);
while (!$q->EOF) {
    if (processAeroplan($q->Fields, isset($opts['f']), isset($opts['d']))) {
        $corrected++;
    }
    $q->Next();
}

echo "done, processed {$q->Position} accounts, deleted info for: {$corrected}\n";

function processAeroplan($fields, $force, $deleteTrips)
{
    global $Connection;
//    echo "a:{$fields['AccountID']}, u:{$fields['UserID']}, upd:{$fields['SuccessCheckDate']}, ec:{$fields['ErrorCode']}, l:{$fields['Login']}, n:{$fields['Number']}";
    $result = false;

    if (empty($fields['Number'])) {
//        echo " - Number not found, skip\n";

        return $result;
    }

    $login = str_replace(' ', '', $fields['Login']);
    if (!is_numeric($login)) {
//        echo " - login is not Account #, skip\n";

        return $result;
    }

    $number = str_replace(' ', '', $fields['Number']);
    if ($number == $login) {
//        echo ", correct account, skip\n";

        return $result;
    }

    echo "a:{$fields['AccountID']}, u:{$fields['UserID']}, upd:{$fields['SuccessCheckDate']}, ec:{$fields['ErrorCode']}, l:{$fields['Login']}, n:{$fields['Number']}";
    echo ", resetting data";
    $result = true;
    if ($force) {
        $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
    }
    removeTrips($fields, $force, $deleteTrips);

    echo "\n";

    return $result;
}

function removeTrips($fields, $force, $deleteTrips)
{
    global $Connection;
    if ($deleteTrips) {
        $Connection->Execute("delete from Trip where AccountID = {$fields['AccountID']} and CreateDate > '2019-09-10'");
        echo ", deleted trips: ".$Connection->GetAffectedRows();
    } else {
        fixAccount($fields, $force);
    }

    return true;
}

function fixAccount($fields, $force)
{
    global $Connection;
    $q = new TQuery("select count(*) as Cnt from Trip where AccountID = {$fields['AccountID']} and CreateDate > '2019-09-10'");
    echo ", deleting trips ({$q->Fields['Cnt']}) and props";
    if ($force) {
        $Connection->Execute("delete from Trip where AccountID = {$fields['AccountID']} and CreateDate > '2019-09-10'");
        echo ", deleted: ".$Connection->GetAffectedRows();
        $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        $Connection->Execute("delete from AccountBalance where AccountID = {$fields['AccountID']} and UpdateDate > '2019-09-10'");
        $qBalance = new TQuery("select max(AccountBalanceID) as AccountBalanceID from AccountBalance where AccountID = {$fields['AccountID']}");
        if (!empty($qBalance->Fields['AccountBalanceID'])) {
            $qBalance = new TQuery("select * from AccountBalance where AccountBalanceID = ".$qBalance->Fields['AccountBalanceID']);
            echo ", correcting last balance";
            $Connection->Execute("update Account set LastChangeDate = '".addslashes($qBalance->Fields['UpdateDate'])."', LastBalance = {$qBalance->Fields['Balance']}, Balance = {$qBalance->Fields['Balance']} where AccountID = {$fields['AccountID']}");
        }
    }
}

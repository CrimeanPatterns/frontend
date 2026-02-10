#!/usr/bin/php
<?
require dirname(__FILE__)."/../web/kernel/public.php";
require_once "$sPath/account/common.php";

echo "updating user stats\n";
$q = new TQuery("
        select UserID
        from Usr
        where LastLogonDateTime > adddate(now(), interval -26 hour)
    union
        SELECT u.UserID
        FROM Usr u
        LEFT JOIN Account a ON (a.UserID = u.UserID)
        WHERE u.Accounts = 0
        GROUP BY u.UserID
        HAVING COUNT(a.AccountID) > 0
");
while(!$q->EOF){
	if(($q->Position % 1000) == 0)
		echo $q->Position." rows processed\n";
	GetAgentFilters($q->Fields['UserID'], "All", $sUserAgentAccountFilter, $sUserAgentCouponFilter, false, false, false);
	$sql = AccountsSQL($q->Fields["UserID"], $sUserAgentAccountFilter, "0 = 1", "", "0 = 1", "All");
	$sql = preg_replace("/\/\*fields\(\*\/.*\/\*\)fields\*\//ims", "count(a.AccountID) as Accounts, count(distinct a.ProviderID) as Providers, count(distinct a.UserAgentID) as UserAgents", $sql);
	$qStat = new TQuery($sql);
	$Connection->Execute(UpdateSQL("Usr", array("UserID" => $q->Fields['UserID']), $qStat->Fields));
	$q->Next();
}
echo $q->Position." rows processed, done\n";

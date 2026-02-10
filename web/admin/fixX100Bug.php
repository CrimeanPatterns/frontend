<?
require "../kernel/public.php";

echo "<h2>fixing x100 bug</h2>";
ob_end_flush();

$q = new TQuery("select
 ab.AccountBalanceID, ab.AccountID, ab.UpdateDate, ab.Balance, abold.Balance as OldBalance, abold.UpdateDate as OldUpdateDate, a.UserID,
 a.LastBalance, p.Code, abold.AccountBalanceID as OldAccountBalanceID
from
 AccountBalance ab
 join AccountBalance abold on ab.AccountID = abold.AccountID and abold.UpdateDate < ab.UpdateDate
and abs(abold.Balance - (ab.Balance / 100)) < (abold.Balance / 10) and abold.Balance > 0
 join Account a on ab.AccountID = a.AccountID
 join Provider p on a.ProviderID = p.ProviderID
where
 ab.UpdateDate > adddate(now(), -1)
 and a.LastBalance = abold.Balance
 and a.Balance = ab.Balance
 and p.AllowFloat = 1");

if(!$q->EOF){
	echo "<table><tr><th>".implode("</th><th>", array_keys($q->Fields))."</th></tr>";
	while(!$q->EOF){
		echo "<tr><td>".implode("</td><td>", $q->Fields)."</td></tr>";
		$Connection->Execute("delete from AccountBalance where AccountBalanceID = {$q->Fields['AccountBalanceID']}");
		$Connection->Execute("update Account set Balance = LastBalance,
		LastChangeDate = ".$Connection->DateTimeToSQL($Connection->SQLToDateTime($q->Fields['OldUpdateDate']))."
		where AccountID = {$q->Fields['AccountID']}");
		$q->Next();
	}
	echo "</table>";
}

echo "<p>Total: <b>{$q->Position}</b></p>";

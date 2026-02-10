#!/usr/bin/php
<?
require __DIR__."/../../web/kernel/public.php";

//echo "searching subaccounts\n";
//$q = new TQuery("select
//	sa.AccountID,
//	a.Balance,
//	trim(trailing '.' from trim(trailing '0' from round(
//		sum(case when sa.Balance = -1 then 0 else sa.Balance end)
//		+ case when a.Balance = -1 then 0 else a.Balance end, 10)
//	)) as TotalBalance
//from
//	SubAccount sa
//	join Account a on sa.AccountID = a.AccountID
//group by
//	sa.AccountID, a.Balance");
//while(!$q->EOF){
//	echo("Account {$q->Fields['AccountID']}: {$q->Fields['Balance']}, {$q->Fields['TotalBalance']}\n");
//	$Connection->Execute("update Account set TotalBalance = {$q->Fields['TotalBalance']} where AccountID = {$q->Fields['AccountID']}");
//	$q->Next();
//}
//echo "done, {$q->Position} processed\n";

echo "updating simple accounts\n";
do{
	$Connection->Execute("update Account set TotalBalance = case when Balance < 0 then 0 else Balance end
	where TotalBalance is null or TotalBalance < 0 limit 500");
	$rows = mysql_affected_rows();

	if($rows == 0){
		// fix south west
		$Connection->Execute("update Account set TotalBalance = Balance
		where TotalBalance is null or TotalBalance = 0 and ProviderID = 16 limit 500");
		$rows = mysql_affected_rows();
	}

	echo "updated {$rows}\n";
}
while($rows > 0);
echo "done\n";

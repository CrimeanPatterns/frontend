<?php

require "../web/kernel/public.php";
require_once "$sPath/account/common.php";
/**
 * @var TMySQLConnection $Connection
 */
global $Connection;
echo "Fixing United Accounts with Balance set to 35000 by error\n";
$sql = "select AccountID, State from Account where ProviderID = 26 and (Balance = 30000 or Balance = 35000) and CheckedBy = 5 and UpdateDate > '2014-01-03'";
$cFixed = 0;
$cDeleted = 0;
$q = new TQuery($sql);
while (!$q->EOF) {
	$fields= $q->Fields;
	$i = null;
	$info = "Account ".$fields["AccountID"];
	if ($fields["State"] == -1) {
		echo "$info - Pending account with balance 35000. Deleting\n";
		DeleteAccount($fields["AccountID"]);
		$cDeleted++;
		$q->Next();
		continue;
	}
	$sql = "select AccountBalanceID, Balance from AccountBalance where AccountID = {$fields["AccountID"]} order by UpdateDate desc limit 2";
	$qBalance = new TQuery($sql);
	if ($qBalance->EOF) {
		echo "$info - AccountBalance empty, skipping\n";
		$q->Next();
		continue;
	}
	$abID = $qBalance->Fields["AccountBalanceID"];
	$qBalance->Next();
	if ($qBalance->EOF) {
		echo "$info - Doesn't have previous AccountBalance, skipping\n";
		$q->Next();
		continue;
	}
	$qBalance->Close();
	echo "$info - Previous balance: ".$qBalance->Fields["Balance"]." Updating account balance, deleting AccountBalance ID = $abID\n";
	$Connection->Execute("update Account set Balance = ".$qBalance->Fields["Balance"]." where AccountID = ".$fields["AccountID"]);
	$Connection->Execute("delete from AccountBalance where AccountBalanceID = ".$abID);
	$cFixed++;
	$q->Next();
}
echo "Fixed accounts: $cFixed\n";
echo "Deleted Pending accounts: $cDeleted\n";

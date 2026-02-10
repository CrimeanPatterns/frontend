<?
require "../kernel/public.php";
$q = new TQuery("select * from AccountBalance order by AccountID, UpdateDate desc");
$nAccountID = null;
$nBalance = null;
ob_end_flush();
echo "checking account history<br>";
while(!$q->EOF){
	if(($q->Fields["AccountID"] == $nAccountID) && ($nBalance == $q->Fields["Balance"])){
		echo "repeating balance: {$q->Fields["AccountID"]}, {$q->Fields["Balance"]}<br>";
		$Connection->Execute("delete from AccountBalance where AccountBalanceID = {$q->Fields["AccountBalanceID"]}");
	}
	$nAccountID = $q->Fields["AccountID"];
	$nBalance = $q->Fields["Balance"];
	$q->Next();
}
echo "complete<br>";
?>
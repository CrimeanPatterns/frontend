<?
require "../kernel/public.php";

ob_end_flush();
echo "converting southwest<br>";
$q = new TQuery("select a.AccountID, a.Balance from Account a
join Provider p on p.ProviderID = a.ProviderID
where p.Code = 'rapidrewards' and a.Balance <> -1");
$arCodes = SQLToArray("select pp.Code, pp.ProviderPropertyID from ProviderProperty pp
		join Provider p on p.ProviderID = pp.ProviderID
		where p.Code = 'rapidrewards'", "Code", "ProviderPropertyID");
while(!$q->EOF){
	$nBalance = floatval($q->Fields['Balance']);
	if($nBalance >= 0)
		$coupons = floor($nBalance);
	else
		$coupons = ceil($nBalance);
	$credits = round(($nBalance - $coupons) * 16, 1);
	echo "account {$q->Fields['AccountID']}, balance: {$nBalance}, coupons: $coupons, credits: $credits<br>";
	$arProperties = array(
		"Credits" => $credits,
		"Coupons" => $coupons
	);
	WriteAccountProperties($q->Fields['AccountID'], null, $arProperties, $arCodes);
	$Connection->Execute("update Account set Balance = -1 where AccountID = {$q->Fields['AccountID']}");
	$q->Next();
}
echo "done<br>";

?>
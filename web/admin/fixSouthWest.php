<?
require "../kernel/public.php";

ob_end_flush();
echo "fixing southwest<br>";
$q = new TQuery("select a.AccountID, a.Balance from Account a
join Provider p on p.ProviderID = a.ProviderID
where p.Code = 'rapidrewards'");
$arCodes = SQLToArray("select pp.Code, pp.ProviderPropertyID from ProviderProperty pp
		join Provider p on p.ProviderID = pp.ProviderID
		where p.Code = 'rapidrewards'", "Code", "ProviderPropertyID");
while(!$q->EOF){
    foreach($arCodes as $code => $providerPropertyId){
        $qProps = new TQuery("select ap.AccountPropertyID, ap.Val from AccountProperty ap where ap.AccountID = {$q->Fields['AccountID']}
        and ap.ProviderPropertyID = {$providerPropertyId} order by ap.AccountPropertyID desc limit 1");
        if(!$qProps->EOF){
            echo "account {$q->Fields['AccountID']}, {$code}: {$qProps->Fields['Val']}<br>";
            $Connection->Execute("delete from AccountProperty where AccountID = {$q->Fields['AccountID']}
            and ProviderPropertyID = {$providerPropertyId} and AccountPropertyID <> {$qProps->Fields['AccountPropertyID']}");
        }
    }
	$q->Next();
}
echo "done<br>";

?>
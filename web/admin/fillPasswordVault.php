<?
require "../kernel/public.php";

$sTitle = "Filling password vault";

require "$sPath/lib/admin/design/header.php";

$limit = intval(ArrayVal($_GET, "Limit", "5"));
$q = new TQuery("select * from Provider where Kind <> ".PROVIDER_KIND_CREDITCARD." and State >= ".PROVIDER_ENABLED);
$count = 0;
while(!$q->EOF){
	$qVault = new TQuery("select
		count(pv.PasswordVaultID) as Cnt
	from
		PasswordVault pv
		join Account a on pv.AccountID = a.AccountID
	where
		a.ProviderID = {$q->Fields['ProviderID']}
		and a.Pass <> ''
		and pv.ExpirationDate > now()");
	if($qVault->Fields['Cnt'] == 0){
		echo "getting accounts for {$q->Fields['Code']} - ";
		$qAcc = new TQuery("select AccountID from Account
		where ProviderID = {$q->Fields['ProviderID']}
		and ErrorCode = ".ACCOUNT_CHECKED."
		and SavePassword = ".SAVE_PASSWORD_DATABASE."
		and Pass <> ''
		limit 1");
		if(!$qAcc->EOF){
			$Connection->Execute(InsertSQL("PasswordVault", array(
				"AccountID" => $qAcc->Fields['AccountID'],
				"ExpirationDate" => "adddate(now(), 90)",
				"CreationDate" => "now()",
			)));
			echo " - added account {$qAcc->Fields['AccountID']}<br>";
		}
		else
			echo " - no valid accounts<br>";
		$count++;
		if($count > $limit){
			echo "limit hit, break<br>";
			break;
		}
	}
	$q->Next();
}
echo "processed: ".$q->Position."<br/>";

require "$sPath/lib/admin/design/footer.php";

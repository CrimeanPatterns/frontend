<?
$schema = "expirations";
require "../start.php";
require_once( "$sPath/lib/classes/TBaseList.php" );

$providerId = intval(ArrayVal($_GET, 'ProviderID'));
$qProv = new TQuery("select * from Provider where ProviderID = $providerId");
if($qProv->EOF)
	DieTrace("Provider not found");

drawHeader("Expirations for ".$qProv->Fields['DisplayName']);

echo "<div style='float: left; width: 49%;'>";
showAccounts("Accounts with Expiration not parsed or set by user", "ProviderID = $providerId and ExpirationAutoSet < ".EXPIRATION_AUTO);
echo "</div>";

echo "<div style='float: right; width: 49%;'>";
showAccounts("Accounts with Expiration parsed", "ProviderID = $providerId and ExpirationAutoSet > ".EXPIRATION_UNKNOWN);
echo "</div>";

drawFooter();

function showAccounts($title, $filter){
	global $Connection;
	echo "<div>$title</div>
	<table border='1' cellpadding='3' cellspacing='0'>";
	$q = new TQuery("select * from Account where SavePassword = ".SAVE_PASSWORD_DATABASE."
	and $filter and ErrorCode = ".ACCOUNT_CHECKED."
	and ExpirationDate is not null order by UpdateDate desc limit 100");
	while(!$q->EOF){
		$q->Fields['ExpirationDate'] = date(DATE_FORMAT, $Connection->SQLToDateTime($q->Fields['ExpirationDate']));
		echo "<tr>
			<td>{$q->Fields['AccountID']}</td>
			<td>{$q->Fields['Login']}</td>
			<td>{$q->Fields['ExpirationDate']}</td>
			<td><a href='/manager/passwordVault/requestPassword.php?ID={$q->Fields['AccountID']}'>Request pass</a></td>
			<td><a href='/manager/impersonate?UserID={$q->Fields['UserID']}'>Impersonate</a></td>
		</tr>";
		$q->Next();
	}
	echo "</table>";

}

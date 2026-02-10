<?
require "../../kernel/public.php";
$userID = 7;
$insertQuery = "INSERT INTO OneCard SET ";
$userRS = new TQuery("SELECT FirstName, LastName FROM Usr WHERE UserID = $userID");
$insertQuery .= "FullName = '{$userRS->Fields["FirstName"]} {$userRS->Fields["LastName"]}', ";
$insertQuery .= "TxtDate = '" . date("F j, Y") . "', ";
$insertQuery .= "OrderDate = NOW(), ";
$userAccountsRS = new TQuery("SELECT AccountID, p.ProviderID, ShortName, Login, Balance FROM Account a
INNER JOIN Provider p ON p.ProviderID = a.ProviderID
WHERE UserID = $userID AND UserAgentID IS NULL
ORDER BY Balance DESC;");
$counter = 0;
while(!$userAccountsRS->EOF AND $counter < 31){
	$counter++;
	$prtefix = "";
	if($counter < 10)
		$prtefix = "0";
	$insertQuery .= "P$prtefix$counter = '{$userAccountsRS->Fields["ShortName"]}', ";
	$accountNumber = new TQuery("SELECT Val FROM AccountProperty ap
INNER JOIN ProviderProperty pp ON ap.ProviderPropertyID=pp.ProviderPropertyID
WHERE AccountID = {$userAccountsRS->Fields["AccountID"]} AND (Code='Number' OR Kind = 1);");
	if(!$accountNumber->EOF)
		$insertQuery .= "A$prtefix$counter = '{$accountNumber->Fields["Val"]}', ";
	else
		$insertQuery .= "A$prtefix$counter = '{$userAccountsRS->Fields["Login"]}', ";
	$eliteStatus = new TQuery("SELECT Val FROM AccountProperty ap
INNER JOIN ProviderProperty pp ON ap.ProviderPropertyID=pp.ProviderPropertyID
WHERE AccountID = {$userAccountsRS->Fields["AccountID"]} AND Kind = 3;");
	if(!$eliteStatus->EOF)
		$insertQuery .= "S$prtefix$counter = '{$eliteStatus->Fields["Val"]}', ";
#	else
#		$insertQuery .= "S$prtefix$counter = NULL, ";
	$insertQuery .= "Ph$prtefix$counter = '1-888-888-8888', ";
	$userAccountsRS->Next();
}
$totalMilesRS = new TQuery("SELECT SUM(Balance) as TotalMiles FROM Account a
INNER JOIN Provider p ON p.ProviderID = a.ProviderID
WHERE UserID = 7 AND UserAgentID IS NULL");
$insertQuery .= "Track1 = 'B4111111111111111^{$userRS->Fields["FirstName"]} {$userRS->Fields["LastName"]}^131210100000000000000000000000000000000', ";
$insertQuery .= "TotalMiles = '" . round($totalMilesRS->Fields["TotalMiles"]) . "';";
print $insertQuery;
$Connection->Execute($insertQuery);
?>
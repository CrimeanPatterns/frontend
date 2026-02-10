<?
function ReplaceRenewMacros($sRenewText, $arFields){
	return str_ireplace('[AccountLogin]', $arFields['Login'], str_ireplace('[AccountID]', $arFields['ID'], str_ireplace("[AccountNumber]", $arFields["Login"], str_ireplace("[RenewLink]", getSymfonyContainer()->get("router")->generate("aw_account_redirect", ["ID" => $arFields['ID'], "Renew" => "1"]), $sRenewText))));
}
?>

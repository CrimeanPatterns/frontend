<?
require "../kernel/public.php";
require_once "$sPath/manager/passwordVault/common.php";

$sTitle = "Fixing password vault";

require "$sPath/lib/admin/design/header.php";

$q = new TQuery("select PasswordVaultID from PasswordVault");
while(!$q->EOF){
	sharePasswordToStaff($q->Fields['PasswordVaultID']);
	$q->Next();
}
echo "processed: ".$q->Position."<br/>";

require "$sPath/lib/admin/design/footer.php";

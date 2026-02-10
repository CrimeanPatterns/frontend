<?
require("../kernel/public.php");
$q = new TQuery("select * from Account where AccountID = ".intval(ArrayVal($_GET, 'ID')));
if($q->EOF)
	die("Account not found");
Redirect("/manager/impersonate?UserID={$q->Fields["UserID"]}&Goto=".urlencode("/account/edit/".$_GET['ID']));
?>

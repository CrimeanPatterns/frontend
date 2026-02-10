<?
$schema = "impersonate";
require( "start.php" );
require_once "$sPath/kernel/TList.php";

drawHeader("Impersonate log");

$list = new TBaseList("ImpersonateLog", array(
	"CreationDate" => array(
		"Type" => "date",
		"IncludeTime" => true,
		"Sort" => "il.CreationDate DESC",
	),
	"User" => array(
		"Type" => "string",
		"FilterField" => "u.Login",
	),
	"Target" => array(
		"Type" => "string",
		"FilterField" => "t.Login",
	),
	"IPAddress" => array(
		"Type" => "string",
		"Caption" => "IP Address",
	),
	"UserAgent" => array(
		"Type" => "string",
	),
), "CreationDate");
$list->SQL = "select
	il.ImpersonateLogID,
	il.CreationDate,
	u.Login as User,
	t.Login as Target,
	il.IPAddress,
	il.UserAgent
from
	ImpersonateLog il
	join Usr u on il.UserID = u.UserID
	join Usr t on il.TargetUserID = t.UserID";
$list->ReadOnly = true;
$list->ShowFilters = true;
$list->Draw();

drawFooter();

?>

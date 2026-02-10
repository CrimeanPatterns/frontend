<?
require "../kernel/public.php";
require_once "../account/common.php";

$s = file_get_contents("$sPath/admin/test.html");
TidyDoc($s);

?>

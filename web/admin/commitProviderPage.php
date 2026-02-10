<?php
require "../kernel/public.php";
require_once "$sPath/schema/ProviderPage.php";

$nID = intval(ArrayVal($_GET, 'ID'));
$Connection->Execute("update ProviderPage set OldHTML = CurHTML, Status = ".PAGE_STATUS_UNCHECKED." where ProviderPageID = $nID");
Redirect(urlPathAndQuery($_SERVER['HTTP_REFERER']));

?>
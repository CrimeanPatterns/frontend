<?php
require "../kernel/public.php";
require_once "$sPath/schema/ProviderPage.php";

$nID = intval(ArrayVal($_GET, 'ID'));
CheckProviderPage($nID);
Redirect(urlPathAndQuery($_SERVER['HTTP_REFERER']));

?>
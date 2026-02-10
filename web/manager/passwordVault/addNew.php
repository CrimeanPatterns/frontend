<?
$schema = "passwords";
require "../start.php";
require_once "$sPath/account/common.php";
require_once "common.php";

$ProviderID = intval(ArrayVal($_GET, 'ProviderID'));
$Login = addslashes(urldecode(ArrayVal($_GET, 'Login')));
$Login2 = addslashes(urldecode(ArrayVal($_GET, 'Login2')));
$login3 = addslashes(urldecode(ArrayVal($_GET, 'Login3')));
$Password = addslashes(urldecode(ArrayVal($_GET, 'Password')));

addToPasswordVault($ProviderID, $Login, $Login2, $login3, $Password);

Redirect("/manager/passwordVault/get.php?ID=$ID");
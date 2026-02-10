<?
require "../kernel/public.php";

AuthorizeUser();
$browser = mysql_quote(substr(ArrayVal($_SERVER, 'HTTP_USER_AGENT'), 0, 250));
$version = mysql_quote(substr(ArrayVal($_POST, 'Version'), 0, 20));

$Connection->Execute("insert into ExtensionInstall(UserID, Browser, InstallDate, Version, InstallCount)
value({$_SESSION['UserID']}, $browser, now(), $version, 1)
on duplicate key update InstallCount = InstallCount + 1, InstallDate = now(), Version = $version");

if(isset($_GET['Target']))
	Redirect(urlPathAndQuery($_GET['Target']));
else
	echo "OK";
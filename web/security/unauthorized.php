<?
require "../kernel/public.php";
// -----------------------------------------------------------------------
// unauthorized
//		user redirected to this page, when he is not authorized
//		user will be redirected to login form
// -----------------------------------------------------------------------

setcookie("PasswordSaved", "", time() - SECONDS_PER_DAY, "/security/", null, true); // remove after 3 months
if(isset($_SESSION['UserID']) && isset($_GET['BackTo']))
	Redirect(urlPathAndQuery($_GET['BackTo']));

$Interface->FooterScripts[] = "authorizeUser();";

?>
<html>
<head>
  <title>Access denied</title>
  <link href="/design/mainStyle.css?v=<?=FILE_VERSION?>" rel="stylesheet" type="text/css"></link>
</head>

<body>
<table cellspacing="0" cellpadding="0" border="0" width="100%" height="100%">
<tr>
	<td align="center" valign="middle">
..:: Access denied ::..<br><br>
You need to login first<br>
The page will reload automatically<br>
Please wait...
<br><br>
	If you don't get re-directed automatically, please <a href="/">click here</a>.
</td>
</tr>
</table>
<?
require "../design/footerCommon.php";
?>
</body>
</html>

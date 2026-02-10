<?php
require "../kernel/public.php";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title><?=SITE_NAME?> Terms of Use</title>
</head>
<body style="margin: 5px; font-family: Verdana; font-size: 12pt;">
<?
$objRS = New TQuery( "SELECT BodyText FROM Forum WHERE ForumID = 9", $Connection );
if(!$objRS->EOF)
	print $objRS->Fields["BodyText"];
?>
</body>
</html>
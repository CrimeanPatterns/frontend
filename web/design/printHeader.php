<?php
StickToMainDomain();
global $sBodyOnLoad;

if ($bSecuredPage) {
    AuthorizeUser();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php
if ($cPage == "forum pages") {
    echo $page_title;
} else {
    echo $sTitle;
}
?></title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<meta name="keywords" content="">
	<meta name="description" content="">
	<meta name="robots" content="index, follow">
	<script language="JavaScript" src="/lib/scripts.js"></script>
	<script type="text/javascript" language="JavaScript" src="/assets/common/vendors/jquery/dist/jquery.min.js"></script>
	<?php require "css.php"; ?>
</head>
<body topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" onload="<?if ($sBodyOnLoad != "") {
    echo $sBodyOnLoad . "; ";
}?>">
<a name="top"></a>

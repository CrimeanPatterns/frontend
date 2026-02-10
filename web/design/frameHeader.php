<?php
StickToMainDomain();
if($bSecuredPage)
	AuthorizeUser();
$closePopup = 0;
$reloadParent = 0;

if(isGranted("SITE_ND_SWITCH")){
	$style = 'background-color: transparent; overflow:hidden; min-width:1px;';
}else{
	$style = 'background-color: transparent;';
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title><?=$sTitle?></title>
	<? require "css.php" ?>
	<? if(isGranted("SITE_ND_SWITCH")) { ?>
	<link rel="stylesheet" type="text/css" href="/assets/awardwalletnewdesign/css/main.css"/>
	<link rel="stylesheet" type="text/css" href="/assets/awardwalletnewdesign/css/jquery-ui.css"/>
	<link rel="stylesheet" type="text/css" href="/assets/awardwalletnewdesign/css/base/oldsite.css"/>
	<? } ?>
</head>

<body id="body" leftmargin="0" topmargin="0" rightmargin="0" bottommargin="0" style="<?=$style?>"  onload="frameLoaded();">
<!-- frame header end -->

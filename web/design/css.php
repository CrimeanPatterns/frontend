<link rel="stylesheet" type="text/css" href="/design/mainStyle.css?v=<?=FILE_VERSION?>"/>
<link rel="stylesheet" href="/lib/3dParty/jquery/themes/itlogy/jquery-ui.css" type="text/css" media="all" />
<!--[if lte IE 8]><link rel="stylesheet" type="text/css" href="/design/ie8.css?v=<?=FILE_VERSION?>"/><![endif]-->
<!--[if lte IE 7]><link rel="stylesheet" type="text/css" href="/design/ie7.css?v=<?=FILE_VERSION?>"/><![endif]-->
<!--[if lte IE 6]><link rel="stylesheet" type="text/css" href="/design/mainStyleIE6.css?v=<?=FILE_VERSION?>"/><![endif]-->
<!--[if lte IE 6]><link rel="stylesheet" type="text/css" href="/design/ie6.css?v=<?=FILE_VERSION?>"/><![endif]-->
<? if(isset($Interface->Skin)) $Interface->Skin->drawCss(); ?>
<? if(SITE_MODE == SITE_MODE_BUSINESS) { ?>
<link rel="stylesheet" type="text/css" href="/design/business.css?v=<?=FILE_VERSION?>"/>
<? } ?>
<? 
if (isset($Interface) && sizeof($Interface->CssFiles)) {
	foreach ($Interface->CssFiles as $file) {
		if (strpos($file, '<!--') === 0)
			echo "$file\n";
		else
			echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"".htmlspecialchars($file)."\" />\n";
	}
}
//header("Content-Security-Policy-Report-Only: default-src 'self' http://awardwallet.com 'unsafe-inline' *.facebook.com *.facebook.net *.doubleclick.net; report-uri /cspReport.php");
header("X-XSS-Protection: 1; mode=block");
?>

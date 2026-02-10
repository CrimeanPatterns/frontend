<?
require( "../kernel/public.php" );
require_once($sPath."/kernel/TForm.php");
require_once($sPath."/lib/cart/public.php");
AuthorizeUser();
if (isGranted("SITE_ND_SWITCH") && SITE_MODE == SITE_MODE_BUSINESS) {
    Redirect("/");
}

# begin determining menus
require($sPath."/design/topMenu/main.php");
require($sPath."/design/leftMenu/award.php");
# end determining menus

NDSkin::setLayout("@AwardWalletMain/Layout/onecard.html.twig");
getSymfonyContainer()->get("aw.widget.onecard_menu")->setActiveItem('order');

$sTitle = "Order placed"; /*rewiew*/
$bSecuredPage = False;
require( "$sPath/design/header.php" );
?>
<link rel="stylesheet" type="text/css" href="/design/onecard.css?v=<?=FILE_VERSION?>"/>
<?

$Interface->DrawBeginBox("style='margin-left: auto; margin-right: auto; width: 660px;'", "Order placed", false, "");

?>
<div style="padding: 20px;">
	Thank you. You will receive your AwardWallet OneCard(s) within 1-2 weeks via US First Class Mail. 
	<br/>
	<br/>
<?=$Interface->DrawButton("Go to My Balances", "onclick=\"document.location.href='/account/list.php?UserAgentID=All'\"")?>
<?=$Interface->DrawButton("Go to Order History", "onclick=\"document.location.href='/onecard/history.php'\"")?>
</div>
<?
$Interface->DrawEndBox();
//$Interface->FooterScripts[] = "showPopupWindow(document.getElementById('funcPopup'), true);";

?>
<script type="text/javascript" src="scripts.js"></script>
<?
require( "$sPath/design/footer.php" );
?>

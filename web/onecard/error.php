<?
require( "../kernel/public.php" );
require_once($sPath."/kernel/TForm.php");
require_once($sPath."/lib/cart/public.php");
AuthorizeUser();

# begin determining menus
require($sPath."/design/topMenu/main.php");
require($sPath."/design/leftMenu/award.php");
# end determining menus

$sTitle = "Awardwallet OneCard Error"; /*checked*/
$bSecuredPage = False;
require( "$sPath/design/header.php" );
$err = ArrayVal($_GET,'err',0);
?>
<link rel="stylesheet" type="text/css" href="/design/onecard.css?v=<?=FILE_VERSION?>"/>
<?

$Interface->DrawBeginBox("style='margin-left: auto; margin-right: auto; width: 660px;'", "Error page of OneCard", false, ""); 

if($err == 1) {
?>
<div class="OCErrBox">
	you do not have enough OneCards
</div>
<?  
} else {
?>
<div class="OCErrBox">
	You do not have any OneCards.
</div>
<?
}
?>
<?=$Interface->DrawButton("Buy AwardWallet OneCard credits", "onclick=\"document.location.href='/user/pay.php'\"") /*checked*/ ?> 
<?
$Interface->DrawEndBox();
//$Interface->FooterScripts[] = "showPopupWindow(document.getElementById('funcPopup'), true);";

?>
<script type="text/javascript" src="scripts.js"></script>
<?
require( "$sPath/design/footer.php" );
?>
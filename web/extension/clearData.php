<?
// -----------------------------------------------------------------------
// Install Plug-in
// Author: Sergey Reutov
// -----------------------------------------------------------------------
require("../kernel/public.php" );
$sTitle = "Clear extension data";
AuthorizeUser();

# begin determining menus
require($sPath."/design/topMenu/main.php");
require($sPath."/design/leftMenu/award.php");
# end determining menus

require( "$sPath/design/header.php" );

?>
Are you sure, you want to delete AwardWallet data from this browser?
<form method="post" name="Delete">
<br/>
<br/>
<? echo $Interface->DrawButton("Delete data", 'onclick="clearExtData(); return false;"'); ?>
<div class="clear"></div>
<script type="text/javascript">

	function clearExtData(){
		browserExt.clear();
		showMessagePopup('info', 'Success', 'Private data removed');
	}

</script>
</form>
<?
require( "$sPath/design/footer.php" );
?>
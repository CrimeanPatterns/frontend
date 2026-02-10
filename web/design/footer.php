<?
global $sPath, $Interface;
if(isset($_GET["printView"]))
	require( "$sPath/design/printFooter.php" );
$Interface->Skin->drawFooter();
?>	

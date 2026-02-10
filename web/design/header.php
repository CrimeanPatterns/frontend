<?
global $leftNavigation, $topMenu, $Connection, $Interface, $sCurrentPage, $bSecuredPage, $sPath, $cPage, $bForceEmailVerification, $sWelcome;

if(!isset($cPage))
	$cPage = "";

if($cPage != "forum pages")
	suggestMobileVersion();

if(!isset($sCurrentPage))
	$sCurrentPage = "";
if( !isset( $_SESSION ) && !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on' )
  session_start();
if($bSecuredPage)
	AuthorizeUser();
if(isset($bForceEmailVerification) && $bForceEmailVerification == true)
	EmailVerify();

# Begin editing content based on if the user is logged in or not...
if(!isset($_SESSION["UserID"])){
	// no user
	$sWelcome = "Welcome to<br><span style=\"font-weight: bold;\">AwardWallet.com</span>";
}
else{
	// user
	$sWelcome = "Welcome back,<br><span style=\"font-weight: bold; font-size: 12px;\">".$_SESSION["UserName"]."</span>";
}
# End editing content based on if the user is logged in or not...
if(isset($QS["printView"]))
	require( "$sPath/design/printHeader.php" );
$Interface->Skin->drawHeader();
?>

<?
require( "../kernel/public.php" );
require_once( "../kernel/TForm.php" );
require_once( "../kernel/TMemberList.php" );

AuthorizeUser();

if (SITE_MODE == SITE_MODE_PERSONAL || (isGranted('USER_BOOKING_PARTNER') && !isGranted('USER_BOOKING_MANAGER')))
    Redirect("/");

if (isGranted('SITE_ND_SWITCH')) {
    Redirect(getSymfonyContainer()->get('router')->generate('aw_business_members'));
}

# begin determining menus
require($sPath."/design/topMenu/main.php");
require($sPath."/design/leftMenu/award.php");
# end determining menus

$sTitle = "Members";
global $forceMenuSelect;
$forceMenuSelect = true;
if(isset($QS['Type']) && !isset($QS['Name']))
	$leftMenu["My Connections"]["selected"] = true;
else
    if (isset($topMenu["Members"]))
        $topMenu["Members"]["selected"] = true;
require( "$sPath/design/header.php" );

$objList = new TMemberList();
$objList->isBookerAdministrator = isGranted('USER_BOOKING_MANAGER') && !isGranted('USER_BUSINESS_ADMIN');

$isEmptyPage = false;
if (strtolower(ArrayVal($_GET, 'goal', 0)) == 'pending') {
	$objList->OpenQuery();
	if ($objList->Query->EOF) {
		$isEmptyPage = true;
		$Interface->DrawMessageBox( "You have no pending members" );
		$objList->DrawButtons();
	}
}
if (!$isEmptyPage)
	$objList->Draw();

require( "$sPath/design/footer.php" );

?>

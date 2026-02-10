<?
$leftMenu = array(
	//"My Award Programs" => array(
	//	"caption"	=> "Corporate accounts", /*checked*/
	//	"path"		=> "/account/list.php",
	//	"selected"	=> false,
	//	"actionPath" => "/account/add.php",
	//	"actionCaption" => "Add a new loyalty program account",
	//),
	"Add a New Program" => array(
		"caption"	=> "Add a New Program",
		"path"		=> "/account/add.php" . ( isset($_GET['UserAgentID']) && is_numeric($_GET['UserAgentID']) ? '?UserAgentID=' . intval($_GET['UserAgentID']) : '' ),
		"selected"	=> false
	),
	"Add New Person" => array(
		"caption"	=> "Add New Person",
		"path"		=> "#",
		"onclick"	=> "showPopupWindow(document.getElementById('newAgentPopup'), true); return false;",
		"selected"	=> false,
	),
	"Add Coupon or Gift Card" => array(
		"caption"	=> "Add Coupon or Gift Card",
		"path"		=> "/coupon/edit.php?ID=0",
		"selected"	=> false
	),
	"My Connections" => array(
		"caption"	=> "Pending members",
		"path"		=> "/agent/members.php?Type=Pending&goal=Pending",
		"selected"	=> false,
		"count"		=> MyConnectionsCount(""),
		"actionPath" => "/agent/addConnection.php",
		"actionCaption" => "Add Connections"
	),
	/*"Convert" => array(
		"caption"	=> "Convert to personal",
		"path"		=> "/agent/convertToPersonal.php",
		"selected"	=> false,
	),*/
	"Update Business Account" => array(
		"caption"	=> "Update Business Account",
		"path"		=> "#",
		"onclick"	=> "showPopupWindow(document.getElementById('needPay'), true); return false;",
		"selected"	=> false,
	),
);
if (isGranted('USER_BOOKING_PARTNER') && !isGranted('USER_BOOKING_MANAGER')) {
	$leftMenu = [];
}

addBookingMenu($bookingCount);

if(isset($topMenu["My Balances"]))
	$topMenu["My Balances"]["selected"] = true;

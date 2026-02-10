<?
AuthorizeUser();

if(SITE_MODE == SITE_MODE_BUSINESS){
	require 'business.php';
}
else{
	$leftMenu = array(
		"My Award Programs" => array(
			"caption"	=> "My Balances",
			"path"		=> "/account/list.php",
			"selected"	=> false,
			"actionPath" => "/account/add.php",
			"actionCaption" => "Add a new loyalty program account",
		),
		"Add a New Program" => array(
			"caption"	=> "Add a New Program",
			"path"		=> "/account/add.php". ( isset($_GET['UserAgentID']) && is_numeric($_GET['UserAgentID']) ? '?UserAgentID=' . intval($_GET['UserAgentID']) : '' ),
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
			"caption"	=> "Manage Users",
			"path"		=> "/agent/connections.php",
			"selected"	=> false,
			"count"		=> MyConnectionsCount(""),
			"actionPath" => "javascript:showPopupWindow(document.getElementById('newAgentPopup'));",
			"actionCaption" => "Add Connections",
		)
	);
	global $Connection;
	$q = new TQuery("select a.AccountID, a.Pass, a.SavePassword, p.DisplayName, a.Login
					from Account a
					join Provider p on a.ProviderID = p.ProviderID
					where a.SavePassword = ".SAVE_PASSWORD_LOCALLY." and a.UserID = {$_SESSION['UserID']} and a.ProviderID is not null limit 0, 1");
	if (!$q->EOF) {
		$leftMenu["Backup passwords"] = array(
			"caption"	=> "Backup Local Passwords"/*checked*/,
			"path"		=> "/account/backupPasswords.php",
			"onclick"	=> "var elem = event.srcElement ? event.srcElement : event.target; askUserPassword('Backup local passwords', 'Get backup', function() { location.href = '/account/backupPasswords.php'; }, elem ); return false;",
			"selected"	=> false
		);
		$leftMenu["Restore passwords"] = array(
			"caption"	=> "Restore Local Passwords"/*checked*/,
			"path"		=> "/account/restorePasswords.php",
			"selected"	=> false
		);
	}
	if(isset($topMenu["My Balances"]))
		$topMenu["My Balances"]["selected"] = true;
	addBookingMenu($bookingCount);
	AddOthersMenu();
}
?>

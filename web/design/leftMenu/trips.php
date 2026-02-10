<?
require_once "$sPath/trips/common.php";

if(isset($_SESSION['Business'])){
	require 'business.php';
	$businessMenu["TravelPlans"]["selected"] = true;
}
else{
	$UaID = (isset($_GET['UserAgentID'])?"&UserAgentID=".($_GET['UserAgentID'] == 'My'?"My":intval($_GET['UserAgentID'])):"");
	$nMyTravelPlansCount = MyTravelPlansCount();
	$myName = 'My Travel Plans';
	if(isset($_SESSION['UserFields']))
		$myName = getNameOwnerAccountByUserFields($_SESSION['UserFields']);
	$leftMenu = array(
		"All Travel Plans" => array(
			"caption"	=> "All Travel Plans",
			"path"		=> "/trips/",
			"selected"	=> false,
			//"count"		=> $nMyTravelPlansCount
		),
		"My Travel Plans" => array(
			"caption"	=> $myName,  #"My Travel Plans",
			"path"		=> "/trips/index.php?UserAgentID=My",
			"selected"	=> false,
			"count"		=> $nMyTravelPlansCount,
			"actionPath" => "/trips/retrieve.php",
			"actionCaption" => "Add a new travel plan",
		),
		"Previous Trip Plans" => array(
			"caption"	=> "Previous Travel Plans",
			"path"		=> "/trips/?Archive=1".$UaID,
			"selected"	=> false,
			"count"		=> PlansCount(1),
		),
		"Deleted Travel Plans" => array(
			"caption"	=> "Deleted Travel Plans",
			"path"		=> "/trips/?Deleted=1".$UaID,
			"selected"	=> false,
			"count" => PlansCount(0,1),
		),
		"Add Travel Plans" => array(
			"caption"	=> "Add Travel Plans",
			"path"		=> "/trips/retrieve.php",
			"selected"	=> false,
		),
		"CalendarAutoImport" => array (
			"caption" => "Calendar auto-import",
			"path" => "javascript:calendarExport();",
			"selected" => false,
		),
		"My Connections" => array(
			"caption"	=> "My Connections",
			"path"		=> "/agent/connections.php?Source=T",
			"selected"	=> false,
			"count"		=> MyConnectionsCount(""),
			"actionPath"=> "javascript:showPopupWindow(document.getElementById('newAgentPopup'));",
			"actionCaption" => "Add connection",
		),
	);

	if (SITE_MODE == SITE_MODE_BUSINESS)
		unset($leftMenu['My Connections']);
	if(isset($topMenu["My Trips"]))
		$topMenu["My Trips"]["selected"] = true;
	AuthorizeUser();
	AddTravelMenu();
	
	if (SITE_MODE == SITE_MODE_BUSINESS) {
		$leftMenu['Map'] = array(
			"caption"	=> "Show Trips Map", /*checked*/
			"path"		=> "/trips/map.php",
			"selected"	=> false,
		);
	}
}
//AddTravelSharingMenu();
?>

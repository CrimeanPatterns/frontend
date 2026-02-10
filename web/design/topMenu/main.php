<?

global $topMenu, $cPage;

if(SITE_MODE == SITE_MODE_BUSINESS){
	$topMenu = array(
		"Accounts" => array(
			"caption"	=> "Accounts",
			"path"		=> "/account/overview.php",
			"selected"	=> false,
		),
		"Members" => array(
			"caption"	=> "Members",
			"path"		=> "/agent/members.php",
			"selected"	=> false,
		),
		"My Trips" => array(
			"caption"	=> "Travel Plans",
			"path"		=> "/trips/",
			"selected"	=> false,
		),
		"Contact Us" => array(
			"caption"	=> "Contact Us",
			"path"		=> "/contact",
			"selected"	=> false
		),
	);
	$topMenu["OneCard"] = array(
		"caption"	=> "OneCard",
		"path"		=> "/onecard/",
		"selected"	=> false,
	);
	$topMenu["Credit Card Offers"] = array(
		"caption"	=> "Credit Card Offers",
		"path"		=> "/cards.php",
		"class"		=> "creditcard_link",
		"selected"	=> false,
	);
	if (isset($_SESSION['UserID'])){
		if (isset($_SESSION['NumberContactsCache'])) {
			$topMenu['Members']['count'] = $_SESSION['NumberContactsCache'];
		}
	}

	if (class_exists('TQuery') && isGranted('USER_BOOKING_PARTNER') && !isGranted('USER_BOOKING_MANAGER')) {
		unset($topMenu['Accounts']);
		unset($topMenu['Members']);
		unset($topMenu['My Trips']);
	}

}
else{
	$topMenu = array(
		"My Balances" => array(
			"caption"	=> "Balances",
			"path"		=> "/account/list.php",
			"selected"	=> false,
			"topButton" => "balances",
			"actionPath" => "/account/add.php" . ( isset($_GET['UserAgentID']) && is_numeric($_GET['UserAgentID']) ? '?UserAgentID=' . intval($_GET['UserAgentID']) : '' ),
			"actionCaption" => "Add new account"
		),
		"My Trips" => array(
			"caption"	=> "Travel Plans",
			"path"		=> "/trips/",
			"selected"	=> false,
			"topButton" => "trips",
			"actionPath" => "/trips/retrieve.php",
			"actionCaption" => "Add new trip"
		),
        "My Offers" => array(
            "caption" => "Savings Offers",
            "path" => "/offers/",
            "selected" => false,
            "topButton" => "offers"
        ),
		"Reviews" => array(
			"caption"	=> "Reviews",
			"path"		=> "/rating/",
			"selected"	=> false
		),
        "Promos" => array(
			"caption"	=> "Promos",
			"path"		=> "/promos",
			"selected"	=> false
		),
		"Contact Us" => array(
			"caption"	=> "Contact Us",
			"path"		=> "/contact",
			"selected"	=> false
		),
		"Help" => array(
			"caption"	=> "FAQs",
			"path"		=> "/faqs.php",
			"selected"	=> false
		),
	);
	$topMenu["OneCard"] = array(
		"caption"	=> "OneCard",
		"path"		=> "/onecard/",
		"selected"	=> false,
	);
	$topMenu["Credit Card Offers"] = array(
		"caption"	=> "Credit Card Offers",
		"path"		=> "/cards.php",
		"class"		=> "creditcard_link",
		"selected"	=> false,
	);
}

if(isset($_SESSION['UserID']) && (SITE_MODE == SITE_MODE_PERSONAL)){
    unset($topMenu['Reviews']);
} else
    unset($topMenu["Promos"]);

if (isset($_SESSION['UserID'])){
// cnt of OneCards	
	$oneCardCnt = getOneCardsCount($_SESSION['UserID']);
	$topMenu["OneCard"]["count"] = $oneCardCnt['Left'];
	$topMenu["OneCard"]["actionPath"] = "/onecard";
}

updateTopMenu();
?>

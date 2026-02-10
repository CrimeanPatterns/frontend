<?
$leftMenu = array(
	"Delete" => array(
		"caption"	=> "Delete my account",
		"path"		=> "/user/delete.php",
		"selected"	=> false,
	),
);

if(isset($_SESSION['HaveABusinessAccount']) && !$_SESSION['HaveABusinessAccount'])
	$leftMenu["Convert"] = array(
		"caption"	=> "Convert to business",
		"path"		=> "/agent/convertToBusiness.php?Start=1",
		"selected"	=> false,
	);
elseif (isset($_SESSION['HaveABusinessAccount']) && $_SESSION['HaveABusinessAccount'])
	$leftMenu["Move"] = array(
		"caption"	=> "Move accounts to business", /*checked*/
		"path"		=> "/agent/moveToBusiness.php",
		"selected"	=> false,
	);

if (SITE_MODE == SITE_MODE_BUSINESS) {
	if (isGranted('USER_BUSINESS_ADMIN'))
		$leftMenu['Delete']['caption'] = "Delete my business account"; /*checked*/
	else
		unset($leftMenu['Delete']);
	unset($leftMenu['Convert']);
}

$leftMenu["Place New Order"] = array(
		"caption"	=> "Place New OneCard Order",   /*checked*/
		"path"		=> "/onecard",
		"selected"	=> false,
	);

$leftMenu["Order History"] = array(
		"caption"	=> "OneCard Order History",   /*checked*/
		"path"		=> "/onecard/history.php",
		"selected"	=> false,
	);

//$leftMenu['ValetKeys'] = array(
//	"caption"	=> "Valet Keys",
//	"path"		=> "/user/valetKeys.php",
//	"selected"	=> false,
//);
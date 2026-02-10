<?
require("../kernel/public.php");
require_once("common.php");
global $Connection;

AuthorizeUser();
if (isGranted("SITE_ND_SWITCH") && SITE_MODE == SITE_MODE_BUSINESS) {
    Redirect("/");
}
$cartID = intval( ArrayVal( $QS, 'CartID', -1 ) );
$userAgentID = intval( ArrayVal( $QS, 'UserAgentID', -1 ) );
if (($cartID > -1) && ($userAgentID > -1)){
    $q = new TQuery("SELECT *
						FROM OneCard
						WHERE CartID = {$cartID}
						AND UserID = {$_SESSION['UserID']}
						AND UserAgentID = {$userAgentID}");
    while (!$q->EOF){
		$Connection->Execute("DELETE FROM OneCard
								WHERE OneCardID = {$q->Fields['OneCardID']}");
		$q->Next();
    } 	       
}

Redirect(urlPathAndQuery(ArrayVal($_SERVER, 'HTTP_REFERER', '/onecard/history.php')));

?>

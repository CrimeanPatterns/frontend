<?
$schema = "Offer";
require "../start.php";
drawHeader("User searcher");
if (isset($_GET["OfferID"])) {
	$q = new TQuery("select Code from Offer where OfferID = '".addslashes($_GET["OfferID"])."'");
	$temp_code = $q->Fields["Code"];
	require "plugins/".ucfirst($temp_code)."OfferPlugin.php";
	$cl = ucfirst($temp_code."OfferPlugin");
	$PluginObject = new $cl($_GET["OfferID"], null);
    session_write_close();
	$PluginObject->UpdateOffer();
    session_start();
}
else {
	echo "This script requires parameters.";   
}
drawFooter();
?>

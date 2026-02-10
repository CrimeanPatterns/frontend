<?
// #6584
$schema = "Offer";
require "../../start.php";
$sTitle = "Reset OfferShowDate for Uber";
$Connection->Execute("
update Usr set OfferShowDate = null where UserID in (select UserID from OfferUser where OfferID = 9)
");
echo "Done";
drawFooter();
?>
<?
require __DIR__ . "/../../web/kernel/public.php";
echo "=== Updating Offers' Users ===\n";
$q = new TQuery("select OfferID, Code from Offer where Enabled = 1 order by OfferID");
while (!$q->EOF){
    $code = $q->Fields['Code'];
    echo $code."\n";
    require __DIR__."/../../web/manager/offer/plugins/".ucfirst($code)."OfferPlugin.php";
    $cl = ucfirst($code."OfferPlugin");
    $PluginObject = new $cl($q->Fields['OfferID'], null);
    $PluginObject->UpdateOffer();
    $q->Next();
}
echo "=== Finished Updating Offers' Users ===\n";
?>

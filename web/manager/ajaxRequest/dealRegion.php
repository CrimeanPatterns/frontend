<?
$schema = "Deal";
require "../start.php";

//http://awardwallet.local/getChildrenAsJson?key=DealRegionID327&mode=funnyMode&_=1320845114255
$parentID = intval(str_replace("DealRegionID","",ArrayVal( $_GET, "key" )));
$DealID = intval(ArrayVal( $_GET, "id" ));

$sql = "SELECT RegionID FROM DealRegion WHERE DealID = $DealID";
$q = new TQuery($sql);
$selectedRegions = array();
while(!$q->EOF){
	$selectedRegions[] = $q->Fields['RegionID'];
	$q->Next();
}

require_once("$sPath/schema/Deal.php");
$objCategoryExplorer = \TDealSchema::GetCategoryExplorer();


require_once("$sPath/kernel/TRegionLinksFieldManager.php");
$objCategoryManager = new TRegionLinksFieldManager();
$objCategoryManager->TableName = "DealRegion";
$objCategoryManager->FieldName = "DealRegionID";
$objCategoryManager->CategoryExplorer = $objCategoryExplorer;
$objCategoryManager->SelectedOptions = $selectedRegions;

header('Content-Type: application/json');
echo $objCategoryManager->TreeHTML2($parentID, 1);

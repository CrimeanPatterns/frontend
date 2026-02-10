<?
$_GET['Schema'] = 'Award';
require( "start.php" );
require_once( "../kernel/TForm.php" );
drawHeader("Replace region");

$objForm = new TForm(array(
	"ProviderID" => array(
	    "Type" => "integer",
		"Caption" => "Loyalty Program",
	    "Required" => True,
	    "Options" => array("" => "Select") + SQLToArray("select ProviderID, DisplayName
	    from Provider
	    order by DisplayName", "ProviderID", "DisplayName"),
		"Value" => ArrayVal($_GET, "ProviderID"),
	),
	"SearchRegion" => array(
		"Required" => True,
		"Type" => "integer",
		"Options" => array("" => "Select") + SQLToArray("select RegionID, Name from Region order by Name", "RegionID", "Name"),
		"Value" => ArrayVal($_GET, "RegionID"),
	),
	"ReplaceWithRegion" => array(
		"Required" => True,
		"Type" => "integer",
		"Options" => array("" => "Select") + SQLToArray("select RegionID, Name from Region order by Name", "RegionID", "Name"),
	),

));
$objForm->SubmitButtonCaption = "Replace";

if($objForm->IsPost && $objForm->Check()){
	$Connection->Execute("update AwardRegionLink arl, Award a
		set arl.RegionID = {$objForm->Fields['ReplaceWithRegion']['Value']}
	where
		arl.RegionID = {$objForm->Fields['SearchRegion']['Value']}
		and arl.AwardID = a.AwardID
		and a.ProviderID = {$objForm->Fields['ProviderID']['Value']}");
	$Interface->DrawMessage("Search done. ".mysql_affected_rows()." records replaced.", "success");
}
else
	echo $objForm->HTML();

echo "<br><br><a href='list.php?Schema=Award&ProviderID=".urlencode(ArrayVal($_GET, 'ProviderID'))."'>Back to list</a>";

drawFooter();
?>

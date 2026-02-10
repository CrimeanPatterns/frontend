<?
$schema = "expirations";
require "../start.php";
require_once( "$sPath/lib/classes/TBaseList.php" );

drawHeader("Expirations");

class TExpirationList extends TBaseList{

	function GetEditLinks(){
		return "<a href=\"expirationDetails.php?ProviderID={$this->OriginalFields['ProviderID']}\">Show accounts</a>
		| <a href=\"/manager/edit.php?Schema=Provider&ID={$this->OriginalFields['ProviderID']}\">Edit provider</a>";
	}

}

$objList = new TExpirationList("Provider", array(
	"ProviderID" => array(
		"Type" => "integer",
		"Sort" => "a.ProviderID ASC",
		"FilterField" => "a.ProviderID",
		"Caption" => "Provider ID",
	),
	"Code" => array(
		"Type" => "string",
		"Size" => 80,
		"Sort" => "p.Code ASC",
		"FilterField" => "p.Code",
	),
	"DisplayName" => array(
		"Type" => "string",
		"Size" => 80,
		"Sort" => "p.DisplayName ASC",
		"FilterField" => "p.DisplayName",
	),
	"Accounts" => array(
		"Type" => "integer",
		"Sort" => "Accounts DESC",
	),
), "Accounts");

$objList->SQL = "select a.ProviderID, p.Code, p.DisplayName, count(a.AccountID) as Accounts from Provider p
join Account a on p.ProviderID = a.ProviderID
where a.ExpirationDate is not null
and a.ExpirationAutoSet = ".EXPIRATION_UNKNOWN."
and a.SavePassword = ".SAVE_PASSWORD_DATABASE."
and a.ErrorCode = ".ACCOUNT_CHECKED."";

$objList->groupBy = "a.ProviderID, p.Code, p.DisplayName";
$objList->ReadOnly = false;
$objList->ShowFilters = true;
$objList->CanAdd = false;
$objList->AllowDeletes = false;
$objList->Draw();

drawFooter();

<?
$schema = "passwords";
require "../start.php";
require_once "$sPath/lib/classes/TBaseList.php";
require_once "$sPath/schema/PasswordVault.php";

class TLogList extends TBaseList{

	function __construct(){
		parent::__construct("PasswordVaultLog", array(
			"EventDate" => array(
				"Type" => "datetime",
				"Sort" => "EventDate DESC",
			),
			"PasswordVaultID" => array(
				"Type" => "integer",
				"Caption" => "PasswordVaultID",
				"FilterField" => "l.PasswordVaultID",
			),
			"UserID" => array(
			    "Type" => "integer",
				"Required" => True,
				"Options" => TPasswordVaultSchema::GetUsers(),
				"Caption" => "User",
				"FilterField" => "l.UserID",
			),
			"DisplayName" => array(
				"Type" => "String",
				"Size" => 60,
			),
			"AccountID" => array(
				"Type" => "integer",
				"Caption" => "AccountID",
			),
			"Login" => array(
				"Type" => "String",
				"Size" => 40,
			),
		), "EventDate");
		$this->SQL = "select
			l.*,
			coalesce(l.Login, a.Login, pv.Login) as Login,
			coalesce(l.AccountID, pv.AccountID) as AccountID,
			p.DisplayName
		from
			PasswordVaultLog l
			left outer join PasswordVault pv on l.PasswordVaultID = pv.PasswordVaultID
			left outer join Account a on pv.AccountID = a.AccountID
			left outer join Provider p on a.ProviderID = p.ProviderID";
		$this->ReadOnly = true;
		$this->ShowFilters = true;
	}

}

drawHeader("Password vault log");

$list = new TLogList();
$list->Draw();

drawFooter();

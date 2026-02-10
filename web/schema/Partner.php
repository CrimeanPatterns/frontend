<?

require_once(__DIR__ . "/UserAdmin.php");

class TPartnerSchema extends TUserAdminSchema
{
	function TuneList( &$list ){
		parent::TuneList( $list );
		$list->SQL = "select u.*, ( select count( t.TransactionID ) from Transaction t where t.UserID = u.UserID and t.State = ".SALE_PENDING." ) as ProgramsAdded from Usr u, SiteGroup sg, GroupUserLink gul where u.UserID = gul.UserID and gul.SiteGroupID = sg.SiteGroupID and sg.GroupName = 'Mileage Brokers'";
	}
	
	function GetListFields(){
		$arFields = parent::GetListFields();
		unset( $arFields['CameFrom'] );
		unset( $arFields['ScreenWidth'] );
		$arFields['ProgramsAdded'] = array(
			"Type" => "integer",
			"Caption" => "Programs Added",
		);
		$arFields['LastLogonDateTime'] = array( 
			"Caption" => "Last log on",
			"Type" => "string",
		);
		return $arFields;
	}
}
?>

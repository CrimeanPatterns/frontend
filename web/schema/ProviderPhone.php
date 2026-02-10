<?

require_once(__DIR__."/../lib/classes/TBaseSchema.php");

class TProviderPhoneSchema extends TBaseSchema
{
	function TProviderPhoneSchema(){
		global $phoneForOptions;
		parent::TBaseSchema();
		$this->TableName = "ProviderPhone";
		$this->ListClass = "ProviderPhoneList";
		$providerId = intval(ArrayVal($_GET, 'ProviderID'));
		if($providerId > 0)
			$eliteLevelWhere = "where el.ProviderID = $providerId";
		else
			$eliteLevelWhere = "";
		$managers = SQLToSimpleArray("select distinct concat('\'', u.Login, '\'') as Login
					from Usr u
					join GroupUserLink gl on u.UserID = gl.UserID
					join SiteGroup g on gl.SiteGroupID = g.SiteGroupID
					where g.GroupName = 'staff' order by Login", "Login");
		$this->Fields = array(
			"ProviderID" => array(
				"Caption" => "Provider",
				"Type" => "integer",
				"Required" => True,
				"Options" => SQLToArray("select ProviderID, DisplayName from Provider order by DisplayName, Code", "ProviderID", "DisplayName"),
			),
			"EliteLevelID" => array(
				"Caption" => "Elite Level",
				"Type" => "integer",
				"Required" => False,
				"Options" => array("" => "") + SQLToArray("select
					el.EliteLevelID,
					min(tel.ValueText) as Title
				from
					EliteLevel el
					join TextEliteLevel tel on el.EliteLevelID = tel.EliteLevelID
				$eliteLevelWhere
				group by
					el.EliteLevelID
				order by
					Title", "EliteLevelID", "Title"),
			),
			"Phone" => array(
				"Type" => "string",
				"Size" => 80,
				"Required" => True,
			),
			"PhoneAction" => array(
				"Caption" 	=> "Phone Action",
				"Type" 		=> "string",
				"Database"  => false,
			),
			"CheckedBy" => array(
				"Type" 		=> "string",
				"Options" 	=> array("" => "") + SQLToArray("select UserID, CONCAT(FirstName, ' ', LastName, ' (', Login, ')') as Login from Usr where Login in (".implode(', ', $managers).")", "UserID", "Login")
			),
			"CheckedDate" => array(
				"Type" 		=> "date",
			),
			"Valid" => array(
				"Type" 		=> "integer",
				"Options" 	=> array(
					"" => "",
					"1" => "Yes",
					"0" => "No",
				)
			),
			"DefaultPhone" => array(
				"Type" => "boolean",
			),
			"Paid" => array(
				"Type" => "integer",
				"Required" => False,
				"Options" => array(
					"" => "Unknown",
					"0" => "Toll free",
					"1" => "Paid",
				),
			),
			"PhoneFor" => array(
				"Type" => "integer",
				"Required" => True,
				"Value" => PHONE_FOR_GENERAL,
				"Options" => $phoneForOptions,
			),
			"CountryID" => array(
				"Type" => "integer",
				"Caption" => "Country",
				"Required" => False,
				"Options" => array("" => "") + SQLToArray("
                select CountryID, Name from Country 
				order by Name", "CountryID", "Name"),
			),
			"DisplayNote" => array(
				"Type" => "string",
				"Size" => 50,
				"Required" => False,
			),
			"Comment" => array(
				"Type" => "string",
				"Size" => 4000,
				"InputType" => "textarea",
			),
		);
		$this->FilterFields = array("ProviderID");
		$this->DefaultSort = "Phone";//"ProviderID";//
		echo '<script type="text/javascript">'.self::getJScheckProviderPhoneFunction().'</script>';
	}

	function GetListFields(){
		$fields = parent::GetListFields();
		unset($fields['Comment']);
		$fields['DefaultPhone']['Type'] = 'integer';
		$fields['DefaultPhone']['Options'] = array(
			"0" => "",
			"1" => "Yes",
		);
		$fields['Popularity'] = array(
			"Caption" => "Provider Popularity",
			"Type" => "integer",
			"InputAttributes" => " readonly",
			"FilterType" => "having",
			"Required" => false,
		);
		return $fields;
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm( $form );
		unset($form->Fields["PhoneAction"]);
		if( isset( $_GET['ProviderID'] ) && ( intval( ArrayVal( $_GET, 'ID' ) ) == 0 ) )
			$form->Fields["ProviderID"]["Value"] = intval( $_GET['ProviderID'] );
		if( isset( $_GET['EliteLevelID'] ) && ( intval( ArrayVal( $_GET, 'ID' ) ) == 0 ) )
			$form->Fields["EliteLevelID"]["Value"] = intval( $_GET['EliteLevelID'] );

        if (false !== $pos = array_search('RegionID', $form->Uniques[0]['Fields'])) unset($form->Uniques[0]['Fields'][$pos]);
		$form->Uniques[] = array(
			"Fields" => array( "ProviderID", "EliteLevelID", "Phone", "PhoneFor", "CountryID" ),
			"ErrorMessage" => "Phone with this Parameters already exists",
		);
		$form->OnSave = array($this, "SaveForm", $form);
		if(Lookup('Provider', 'ProviderID', 'Code', intval(ArrayVal($_GET, 'ProviderID'))) == 'rewardsnet'){
			$form->Fields['DisplayNote']['Options'] = array("" => "") + TAccountCheckerRewardsnet::getNameOptions();
		}
	}

	function TuneList( &$list ){
		parent::TuneList( $list );
		$list->SQL = str_replace('Popularity', '(select Accounts from Provider p where p.ProviderID = pp.ProviderID) as Popularity', $list->SQL);
		$list->SQL .= ' pp where 1 = 1 ';
	}

	function SaveForm($objForm){
		global $Connection;
		if($objForm->Fields['DefaultPhone']['Value'] == '1'){
			$sql = "update ProviderPhone set DefaultPhone = null
			where ProviderID = {$objForm->Fields['ProviderID']['SQLValue']}
			and ProviderPhoneID <> {$objForm->ID}";
			if(isset($objForm->Fields['EliteLevelID']['Value']))
				$sql .= " and EliteLevelID = ".$objForm->Fields['EliteLevelID']['SQLValue'];
			else
				$sql .= " and EliteLevelID is null";
			$Connection->Execute($sql);
		}
	}

	public static function getPhoneActions($providerPhoneID) {
		$links = array();
		$links[] = '<span style="cursor: pointer; text-decoration: underline;" onClick="checkProviderPhone(this, \''.$providerPhoneID.'\', \'1\');">Yes</span>';
		$links[] = '<span style="cursor: pointer; text-decoration: underline;" onClick="checkProviderPhone(this, \''.$providerPhoneID.'\', \'0\');">No</span>';
		$links[] = '<span style="cursor: pointer; text-decoration: underline;" onClick="checkProviderPhone(this, \''.$providerPhoneID.'\', \'2\');">Check</span>';
		return '<div style="font-size: 9px">'.implode(" ", $links).'</div>';
	}

	public static function getJScheckProviderPhoneFunction() {
		return "
			function checkProviderPhone(context, providerPhoneID, state) {
				$.ajax({
				  url: '/manager/checkProviderPhone.php',
				  dataType: 'json',
				  data: {'providerPhoneID': providerPhoneID, 'state': state},
				  success: function(data){
		  			if (typeof data.error != 'undefined')
		  				return;
	  				
	  				$(context).closest('tr').find('td:eq(6)').html(data.CheckedBy);
	  				$(context).closest('tr').find('td:eq(7)').html(data.CheckedDate);
	  				$(context).closest('tr').find('td:eq(8)').html(data.Valid);
		  		  }
				});
			}
		";
	}

	public static function getPhonesLink($providerId, $eliteLevelId){
		$phones = SQLToSimpleArray("select distinct Phone from ProviderPhone
		where ProviderID ".(empty($providerId)?" is null":" = ".$providerId)." and EliteLevelID ".(empty($eliteLevelId)?" is null":" = ".$eliteLevelId), "Phone");
		if(count($phones) == 0)
			$result = "None";
		else
			if(count($phones) <= 3)
				$result = implode(", ", $phones);
			else
				$result = implode(", ", array_slice($phones, 0, 3))." and ".(count($phones) - 3)." more";
		return "<a href='list.php?Schema=ProviderPhone&ProviderID={$providerId}&EliteLevelID={$eliteLevelId}'>{$result}</a>";
	}

}

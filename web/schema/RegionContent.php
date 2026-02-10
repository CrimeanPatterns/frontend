<?

class TRegionContentSchema extends TBaseSchema
{
	function TRegionContentSchema(){
		parent::TBaseSchema();
		$this->TableName = "RegionContent";
		$objStateFieldManager = new TRegionStateFieldManager();
		$objStateFieldManager->CountryField = "CountryCode";
		$this->Fields = array(
			"RegionID" => array(
				"Caption" => "Parent Region",
				"Type" => "integer",
				"Required" => True,
				"Options" => SQLToArray("select RegionID, Name from Region order by Name", "RegionID", "Name"),
			),
			"SubRegionID" => array(
				"Caption" => "Child Region",
				"Type" => "integer",
				"Options" => array("" => "None") + SQLToArray("select RegionID, Name from Region order by Name", "RegionID", "Name"),
			),
			/*"CountryCode" => array(
				"Caption" => "Country",
				"Type" => "string",
				"Size" => 3,
				"InputAttributes" => "style=\"width: 300px;\" onchange=\"this.form.DisableFormScriptChecks.value=1;var stateInput = this.form.StateCode; stateInput.selectedIndex = 0; EnableFormControls( this.form );this.form.submit();\"",
				"Options" => array("" => "None") + SQLToArray("select distinct AirCountryCode, CountryName 
				from AirCode where AirCountryCode is not null and AirCountryCode <> ''
				and AirCountryCode <> '+' order by CountryName", "AirCountryCode", "CountryName"),
				"Sort" => "CountryCode ASC, StateCode ASC, Exclude"
			),
			"StateCode" => array(
				"Caption" => "State",
				"Type" => "string",
				"Size" => 8,
				"Manager" => $objStateFieldManager,
				"Options" => array("" => "None"),
			),
			"AirPortCode" => array(
				"Caption" => "Airport Code",
				"Type" => "string",
				"Size" => 3,
			),*/
			"Exclude" => array(
				"Caption" => "Exclude",
				"Type" => "boolean",
			),
		);
		$this->FilterFields = array( "SubRegionID" );
		$this->DefaultSort = "CountryCode";
	}
	
	function GetListFields(){
		$arFields = parent::GetListFields();
		unset( $arFields["StateCode"]["Options"] );
		return $arFields;
	}
	
	function TuneForm(\TBaseForm $form){
		parent::TuneForm( $form );
		if( isset( $_GET['RegionID'] ) )
			unset( $form->Fields["SubRegionID"]["Options"][intval( $_GET['RegionID'] )] );
			if( intval( ArrayVal( $_GET, 'ID' ) ) == 0 ){
				$form->Fields["RegionID"]["Value"] = intval( $_GET['RegionID'] );
		}
		$form->Uniques[] = array(
			"Fields" => array( "RegionID", "SubRegionID", "StateCode", "CountryCode", "AirPortCode" ),
			"Message" => "Property with this Name already exists",
		);
	}
}

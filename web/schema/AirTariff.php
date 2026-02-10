<?

class TAirTariffSchema extends TBaseSchema
{
	function TAirTariffSchema(){
		parent::TBaseSchema();
		$this->TableName = "AirTariff";
		$this->Fields = array(
			"ProviderID" => array( 
			    "Type" => "integer",
				"Caption" => "Provider",
			    "Required" => True, 
			    "Options" => SQLToArray("select ProviderID, Name 
			    from Provider
			    where Kind = ".PROVIDER_KIND_AIRLINE."  
			    order by Name", "ProviderID", "Name"),
			),
			"SrcRegionID" => array( 
			    "Type" => "integer",
				"Caption" => "From Region",
				"Options" => SQLToArray("select RegionID, Name 
			    from Region order by Name", "RegionID", "Name"),
				"Required" => True 
			),
			"DstRegionID" => array( 
			    "Type" => "integer",
				"Caption" => "To Region",
				"Options" => SQLToArray("select RegionID, Name 
			    from Region order by Name", "RegionID", "Name"),
				"Required" => True 
			),
			"DateRangeID" => array( 
			    "Type" => "integer",
				"Caption" => "Dates",
				"Options" => array( "" => "None" ) 
				+ SQLToArray("select DateRangeID, Name 
			    from DateRange order by Name", "DateRangeID", "Name"),
				"Required" => False 
			),
			"PriceEconomy" => array( 
			    "Type" => "integer",
				"RequiredGroup" => "price",
			),
			"PriceBusiness" => array( 
			    "Type" => "integer",
				"RequiredGroup" => "price",
			),
			"PriceFirst" => array( 
			    "Type" => "integer",
				"RequiredGroup" => "price",
			),
			"RoundTrip" => array( 
			    "Type" => "boolean",
				"Required" => True,
				"Value" => "1",
			),
		);
	}
	
	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		$form->Uniques = array(
		  array( 
		    "Fields" => array( "ProviderID", "SrcRegionID", "DstRegionID", "DateRangeID" ),
		    "ErrorMessage" => "This tariff already exists. Please choose another parameters."
		  )
		);
		if( isset( $_GET['ProviderID'] ) && ( intval( ArrayVal( $_GET, 'ID' ) ) == 0 ) ){
			$form->Fields["ProviderID"]["Value"] = intval( $_GET['ProviderID'] );
		}
		if( isset( $_GET['SrcRegionID'] ) ) 
			if( intval( ArrayVal( $_GET, 'ID' ) ) == 0 ){
				$form->Fields["SrcRegionID"]["Value"] = intval( $_GET['SrcRegionID'] );
		}
	}
	
}
?>

<?

class THotelTariffSchema extends TBaseSchema
{
	function THotelTariffSchema(){
		parent::TBaseSchema();
		$this->TableName = "HotelTariff";
		$this->Fields = array(
			"ProviderID" => array( 
			    "Type" => "integer",
				"Caption" => "Provider",
			    "Required" => True, 
			    "Options" => SQLToArray("select ProviderID, Name 
			    from Provider
			    where Kind = ".PROVIDER_KIND_HOTEL." 
			    order by Name", "ProviderID", "Name"),
			),
			"WeekDayStart" => array( 
			    "Type" => "integer",
				"Min" => "1",
				"Max" => "7",
				"Value" => "1",
			),
			"WeekDayEnd" => array( 
			    "Type" => "integer",
				"Min" => "1",
				"Max" => "7",
				"Value" => "7",
			),
			"Days" => array( 
			    "Type" => "integer",
				"Min" => "1",
				"Value" => "1",
			),
			"PriceOpp" => array( 
			    "Type" => "integer",
				"Caption" => "Price opportunity",
				"RequiredGroup" => "price",
			),
			"Price1" => array( 
			    "Type" => "integer",
				"RequiredGroup" => "price",
			),
			"Price2" => array( 
			    "Type" => "integer",
				"RequiredGroup" => "price",
			),
			"Price3" => array( 
			    "Type" => "integer",
				"RequiredGroup" => "price",
			),
			"Price4" => array( 
			    "Type" => "integer",
				"RequiredGroup" => "price",
			),
			"Price5" => array( 
			    "Type" => "integer",
				"RequiredGroup" => "price",
			),
			"Price6" => array( 
			    "Type" => "integer",
				"RequiredGroup" => "price",
			),
			"Price7" => array( 
			    "Type" => "integer",
				"RequiredGroup" => "price",
			),
		);
	}
	
	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		$form->Uniques = array(
		  array( 
		    "Fields" => array( "ProviderID", "WeekDayStart", "WeekDayEnd", "Days" ),
		    "ErrorMessage" => "This tariff already exists. Please choose another parameters."
		  )
		);
		if( isset( $_GET['ProviderID'] ) && ( intval( ArrayVal( $_GET, 'ID' ) ) == 0 ) ){
			$form->Fields["ProviderID"]["Value"] = intval( $_GET['ProviderID'] );
		}
	}
	
}
?>

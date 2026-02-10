<?

class TDateRangeSchema extends TBaseSchema
{
	function TDateRangeSchema(){
		parent::TBaseSchema();
		$this->TableName = "DateRange";
		$this->Fields = array(
			"Name" => array( 
			    "Type" => "string",
			    "Size" => 80,
			    "Required" => True 
			),
			"StartDate" => array( 
			    "Type" => "date",
			    "Required" => True 
			),
			"EndDate" => array( 
			    "Type" => "date",
			    "Required" => True 
			),
		);
	}
	
	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		$form->Uniques = array(
		  array( 
		    "Fields" => array( "Name" ),
		    "ErrorMessage" => "This date range already exists. Please choose another name."
		  )
		);
	}
	
}
?>

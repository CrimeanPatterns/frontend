<?

class TBadWordSchema extends TBaseSchema
{
	function TBadWordSchema(){
		parent::TBaseSchema();
		$this->TableName = "BadWord";
		$this->Fields = array(
			"Word" => array( 
			    "Type" => "string",
			    "Size" => 80,
			    "Required" => True 
			),
		);
	}
	
	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		$form->Uniques = array(
		  array( 
		    "Fields" => array( "Word" ),
		    "ErrorMessage" => "This word already exists. Please choose another word."
		  )
		);
	}

}
?>

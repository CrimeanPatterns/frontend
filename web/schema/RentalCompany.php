<?

class TRentalCompanySchema extends TBaseSchema
{
	function TRentalCompanySchema(){
		parent::TBaseSchema();
		$this->TableName = "RentalCompany";
		$this->Fields = array(
			"Name" => array( 
			    "Type" => "string",
			    "Size" => 200,
			    "Cols" => 40,
			    "Required" => True ),
		);
	}

	function GetListFields(){
		$arFields = parent::GetListFields();
		return $arFields;
	}
	
	function TuneList(&$list){
		parent::TuneList( $list );
	}
	
	function GetFormFields(){
		$arFields = parent::GetFormFields();
		return $arFields;
	}
	
	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		$form->Uniques = array(
		  array( 
		    "Fields" => array( "Name" ),
		    "ErrorMessage" => "Company with this Name already exists. Please choose another Name."
		  ),
		);
	}
}
?>

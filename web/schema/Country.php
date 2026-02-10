<?

class TCountrySchema extends TBaseSchema
{
	function __construct(){
		parent::TBaseSchema();
		$this->TableName = "Country";
		$this->Fields = array(
            "Code" => array(
         			    "Type" => "string",
         			    "Size" => 2,
         			    "InputAttributes" => "style='width: 300px;'",
         			),
			"Name" => array(
			    "Type" => "string",
			    "Size" => 250,
			    "Required" => True,
				"InputAttributes" => "style='width: 300px;'",
				"Caption" => "Name (Plural)<br>(ex: Rewards, Dollars etc.)",
			),
			"HaveStates" => array(
			    "Type" => "boolean",
			    "Required" => True,
                "Value" => 0,
			),
		);
	}

	function GetFormFields(){
		$fields = parent::GetFormFields();
		return $fields;
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		$form->Uniques = array(
		  	array(
		    	"Fields" => array("Code"),
		    	"ErrorMessage" => "This code already exists. Please choose another code."
		  	),
		  	array(
		    	"Fields" => array("Name"),
		    	"ErrorMessage" => "This name already exists. Please choose another name."
		  	),
		);
	}

}

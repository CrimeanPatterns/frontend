<?

class TAllianceSchema extends TBaseSchema
{
	function __construct(){
		parent::TBaseSchema();
		$this->TableName = "Alliance";
		$this->Fields = array(
			"Name" => array(
			    "Type" => "string",
			    "Size" => 250,
			    "Required" => True,
				"InputAttributes" => "style='width: 300px;'",
			),
		);
	}

	function GetFormFields(){
		$fields = parent::GetFormFields();
		$levelManager = new TTableLinksFieldManager();
		$levelManager->TableName = "AllianceEliteLevel";
		$levelManager->Fields = array(
			"Rank" => array(
				"Type" => "integer",
				"Required" => true,
			),
			"Name" => array(
				"Type" => "string",
				"Required" => true,
				"Size" => 80,
			),
		);
		$levelManager->CanEdit = true;
		$levelManager->AutoSave = true;
		$fields['EliteLevels'] = array(
			"Type" => "string",
			"Manager" => $levelManager,
		);
		return $fields;
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		$form->Uniques = array(
		  	array(
		    	"Fields" => array("Name"),
		    	"ErrorMessage" => "This name already exists. Please choose another name."
		  	)
		);
	}

}
?>

<?

class TAwardTypeSchema extends TBaseSchema
{
	function TAwardTypeSchema(){
		parent::TBaseSchema();
		$this->TableName = "AwardType";
		$this->Fields = array(
			"Name" => array(
			    "Type" => "string",
				"Required" => True,
				"Size" => 80,
				"Required" => True,
				"InputAttributes" => " style='width: 300px;'",
			),
		);
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		$form->Uniques[] = array(
			"Fields" => array("Name"),
			"ErrorMessage" => "Type with this name already exists",
		);
	}

	function TuneList(&$list){
		parent::TuneList($list);
		$list->ShowBack = true;
	}
}
?>

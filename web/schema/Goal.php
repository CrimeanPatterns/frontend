<?

class TGoalSchema extends TBaseSchema
{
	function TGoalSchema(){
		parent::TBaseSchema();
		$this->TableName = "Goal";
		$this->Fields = array(
			"Name" => array(
			    "Type" => "string",
				"Size" => 120,
				"Required" => true,
			),
			"SortIndex" => array(
			    "Type" => "integer",
				"Required" => true,
			),
		);
		$this->DefaultSort = "SortIndex";
	}

	function GetFormFields(){
		$arFields = parent::GetFormFields();
		if(intval(ArrayVal($_GET, 'ID')) > 0){
			$arFields["GoalTarget"] = array(
				"Type" => "html",
				"Caption" => "Goal targets",
				"HTML" => "<a href=\"list.php?Schema=GoalTarget&GoalID=".urlencode($_GET['ID'])."\">View/Edit</a>",
			);
		}
		return $arFields;
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		$form->Uniques = array(
		  array(
		    "Fields" => array("Name" ),
		    "ErrorMessage" => "This goal already exists. Please choose another name."
		  )
		);
		if($form->ID == 0){
			$q = new TQuery("select SortIndex from Goal order by SortIndex desc limit 1");
			if( !$q->EOF )
				$form->Fields["SortIndex"]["Value"] = $q->Fields["SortIndex"] + 10;
			else
				$form->Fields["SortIndex"]["Value"] = 10;
		}
	}

}
?>

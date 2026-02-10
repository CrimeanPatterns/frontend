<?

class TGoalTargetSchema extends TBaseSchema
{
	function TGoalTargetSchema(){
		parent::TBaseSchema();
		$this->TableName = "GoalTarget";
		$this->Fields = array(
			"GoalID" => array(
				"Caption" => "Goal",
				"Type" => "integer",
				"Required" => True,
				"Options" => SQLToArray("select GoalID, Name from Goal order by Name", "GoalID", "Name"),
				"InplaceEdit" => false,
			),
			"ProviderID" => array(
				"Caption" => "Provider",
				"Type" => "integer",
				"Required" => True,
				"Options" => SQLToArray("select ProviderID, Name from Provider order by Name", "ProviderID", "Name"),
				"InplaceEdit" => false,
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
		);
		$this->DefaultSort = "ProviderID";
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		if( intval( ArrayVal( $_GET, 'ID' ) ) == 0 ){
			if(isset( $_GET['ProviderID'] ))
				$form->Fields["ProviderID"]["Value"] = intval( $_GET['ProviderID'] );
			if(isset( $_GET['GoalID'] ))
				$form->Fields["GoalID"]["Value"] = intval( $_GET['GoalID'] );
		}
		$form->Uniques[] = array(
			"Fields" => array( "ProviderID", "GoalID" ),
			"Message" => "Target with this Provider/Goal already exists",
		);
	}

	function TuneList(&$list){
		parent::TuneList( $list );
		$list->InplaceEdit = true;
	}

}
?>

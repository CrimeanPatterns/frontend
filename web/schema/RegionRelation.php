<?

class TRegionRelationSchema extends TBaseSchema
{
	function TRegionRelationSchema(){
		parent::TBaseSchema();
		$this->TableName = "RegionContent";
		$this->Fields = array(
			"RegionParentID" => array(
			    "Type" => "integer",
				"Required" => True,
				"Options" => SQLToArray("select RegionID, RegionName from Region
				order by RegionName", "RegionID", "RegionName" ),
				"Caption" => "Parent Region",
			),
			"RegionID" => array(
			    "Type" => "integer",
				"Required" => True,
				"Options" => SQLToArray("select RegionID, RegionName from Region
				order by RegionName", "RegionID", "RegionName" ),
				"Caption" => "Child Region",
			),
		);
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		$form->Uniques = array(
		  array(
		    "Fields" => array( "RegionParentID", "RegionID" ),
		    "ErrorMessage" => "This region relation already exists. Please choose another parameters."
		  )
		);
		if( isset( $_GET['RegionParentID'] ) && ( intval( ArrayVal( $_GET, 'ID' ) ) == 0 ) )
			$form->Fields["RegionParentID"]["Value"] = intval( $_GET['RegionParentID'] );
	}

}
?>

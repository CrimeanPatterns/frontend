<?

class TProviderInputOptionSchema extends TBaseSchema
{
	function TProviderInputOptionSchema(){
		parent::TBaseSchema();
		$this->TableName = "ProviderInputOption";
		$this->Fields = array(
			"FieldName" => array(
				"Caption" => "Field Name",
				"Type" => "string",
				"Required" => True,
				"Options" => array('Login2', 'Login3'),
			),
			"ProviderID" => array(
				"Caption" => "Provider",
				"Type" => "integer",
				"Required" => True,
				"Options" => SQLToArray("select ProviderID, DisplayName from Provider order by DisplayName", "ProviderID", "DisplayName"),
			),
			"Name" => array(
				"Type" => "string",
				"Size" => 80,
				"Required" => True,
				"Sort" => "ProviderID,Name,SortIndex"
			),
			"Code" => array(
				"Type" => "string",
				"Size" => 40,
				"Required" => True,
				"Sort" => "ProviderID,Code"
			),
			"SortIndex" => array(
				"Type" => "integer",
				"Required" => True,
				"Sort" => "ProviderID,SortIndex"
			),
		);
		$this->FilterFields = array( "ProviderID" );
		$this->DefaultSort = "SortIndex";
	}

	function TuneList(&$list){
		parent::TuneList($list);
		if(ArrayVal($_SERVER, 'REMOTE_USER') == 'points'){
			$list->ShowImport = false;
			$list->AllowDeletes = false;
			$list->CanAdd = false;
			$list->MultiEdit = false;
		}
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm( $form );
		if( isset( $_GET['ProviderID'] ) && ( intval( ArrayVal( $_GET, 'ID' ) ) == 0 ) ){
			$form->Fields["ProviderID"]["Value"] = intval( $_GET['ProviderID'] );
			$q = new TQuery("select SortIndex from ProviderProperty where ProviderID = ".intval( $_GET['ProviderID'] )." order by SortIndex desc limit 1");
			if( !$q->EOF )
				$form->Fields["SortIndex"]["Value"] = $q->Fields["SortIndex"] + 10;
			else
				$form->Fields["SortIndex"]["Value"] = 10;
		}
		$form->Uniques[] = array(
			"Fields" => array( "ProviderID", "Name" ),
			"Message" => "Property with this Name already exists",
		);
		$form->Uniques[] = array(
			"Fields" => array( "ProviderID", "Code" ),
			"Message" => "Property with this Code already exists",
		);
		if(ArrayVal($_SERVER, 'REMOTE_USER') == 'points'){
			$form->SubmitButtonCaption = "Cancel";
			$form->ReadOnly = true;
		}
	}
}

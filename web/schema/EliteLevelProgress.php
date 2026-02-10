<?

class TEliteLevelProgressSchema extends TBaseSchema
{

	function TEliteLevelProgressSchema(){
		parent::TBaseSchema();
		$this->TableName = "EliteLevelProgress";
		$this->ListClass = "EliteLevelProgressList";
		$PPArr = array("" => "");
		$SLArr = array("" => "");
		if (isset($_GET["Provider"]) && $_GET["Provider"] != "") {
			$providerFilter = " where ProviderID = {$_GET["Provider"]} ";
			$PPArr += SQLToArray("select CONCAT(Name, ' - ', Code) as Name, ProviderPropertyID
									   from ProviderProperty".$providerFilter.
									  "order by Name",
				"ProviderPropertyID", "Name");
			$SLArr += SQLToArray("select Name, EliteLevelID
											   from EliteLevel" . $providerFilter .
					"order by `Rank`",
				"EliteLevelID", "Name");
		}
		$this->Fields = array(
			"EliteLevelProgressID" => array(
				"Caption" => "id",
				"Type" => "integer",
				"Required" => True,
				"InputAttributes" => " readonly",
				"filterWidth" => 30,
				"InplaceEdit" => False,
			),
			"Provider" => array(
				"Caption" => "Provider",
				"Type" => "string",
				"InplaceEdit" => false,
				"InputAttributes" => " readonly",
				"Database" => false,
			),
			"ProviderPropertyID" => array(
				"Caption" => "ProviderProperty",
				"Type" => "integer",
				"Required" => True,
				"Options" => $PPArr,
				"InplaceEdit" => False,
			),
			"StartDatePropertyID" => array(
				"Caption" => "StartDateProperty",
				"Type" => "integer",
				"Required" => False,
				"Options" => $PPArr,
				"InplaceEdit" => False,
			),
			"EndMonth" => array(
				"Caption" => "EndMonth",
				"Type" => "integer",
				"Required" => False,
				"Size" => 3,
				"Cols" => 3,
				"filterWidth" => 20,
				"InplaceEdit" => False,
			),
			"EndDay" => array(
				"Caption" => "EndDay",
				"Type" => "integer",
				"Required" => False,
				"Size" => 3,
				"Cols" => 3,
				"filterWidth" => 20,
				"InplaceEdit" => False,
			),
			"StartLevelID" => array(
				"Caption" => "Start Level",
				"Type" => "integer",
				"Required" => False,
				"Size" => 3,
				"Cols" => 3,
				"Options" => $SLArr,
				"filterWidth" => 20,
				"InplaceEdit" => False,
			),
			"GroupIndex" => array(
				"Caption" => "GroupIndex",
				"Type" => "integer",
				"Size" => 3,
				"Cols" => 3,
				"filterWidth" => 20,
				"Required" => False,
				"InplaceEdit" => true
			),
            'Position' => [
                'Type' => 'integer',
                'Size' => 3,
                'Cols' => 3,
                'filterWidth' => 20,
                'Required' => false,
                'InplaceEdit' => true,
                'Note' => 'Only in combination with fields Group and Operator',
            ],
            'GroupID' => [
                'Caption' => 'Group',
                'Type' => 'integer',
                'Size' => 3,
                'Cols' => 3,
                'filterWidth' => 20,
                'Required' => false,
                'InplaceEdit' => true,
                'Note' => 'Only in combination with fields Position and Operator',
            ],
            'Operator' => [
                'Type' => 'integer',
                'Options' => [
                    '' => '',
                    1 => 'OR',
                    2 => 'AND',
                ],
                'Note' => 'Only in combination with fields Position and Group',
            ],
			"ToNextLevel" => array(
				"Caption" => "Needed to next level",
				"Type" => "boolean",
				"Size" => 3,
				"Cols" => 3,
				"filterWidth" => 20,
				"Required" => False,
				"InplaceEdit" => False
			),
			"Lifetime" => array(
				"Caption" => "Lifetime",
				"Type" => "boolean",
				"Required" => False,
				"Size" => 3,
				"Cols" => 3,
				"filterWidth" => 20,
				"InplaceEdit" => False,
			),
		);
	}
	function GetListFields() {
		$arFields = parent::GetListFields();
		//unset($arFields["EndMonth"]);
		//unset($arFields["EndDay"]);
		$arFields["Values"] = array(
			"Caption" => "Values",
			"Type" => "string",
			"InplaceEdit" => False,
			"Database" => false,
		);
		return $arFields;
	}
	function TuneList(&$list){
		parent::TuneList($list);
		$list->InplaceEdit = true;
		if(ArrayVal($_SERVER, 'REMOTE_USER') == 'points'){
			$list->ShowImport = false;
			$list->AllowDeletes = false;
			$list->CanAdd = false;
			$list->MultiEdit = false;
		}
		if (!isset($_GET["Provider"]) || $_GET["Provider"] == "")
			$list->CanAdd = false;
	}

	function GetFormFields(){
		$fields = parent::GetFormFields();
		unset($fields["EliteLevelProgressID"]);

		$providerFilter = " ";
		$providerID = 0;
		if (isset($_GET["Provider"]) && $_GET["Provider"] != "")
			$providerID = intval($_GET["Provider"]);
		else
			if (isset($_GET["ID"]) && $_GET["ID"] != "" && $_GET["ID"] != 0) {
				$ppID = Lookup("EliteLevelProgress", "EliteLevelProgressID", "ProviderPropertyID", intval($_GET["ID"]));
				$providerID = Lookup("ProviderProperty", "ProviderPropertyID", "ProviderID", $ppID);
			}
		if ($providerID > 0) {
			$providerFilter = " where ProviderID = {$providerID} ";
			$fields["Provider"]["Value"] = Lookup("Provider", "ProviderID", "DisplayName", $providerID);

		}
		else
			unset($fields["Provider"]);
		$PPArr = SQLToArray("select CONCAT(Name, ' - ', Code) as Name, ProviderPropertyID
								   from ProviderProperty".$providerFilter.
								  "order by Name",
			"ProviderPropertyID", "Name");

		$ELArr = SQLToArray("select EliteLevelID, Name
								  from EliteLevel".$providerFilter.
								 "order by `Rank`",
			"EliteLevelID", "Name");

		$fields["ProviderPropertyID"]["Options"] = array("" => "") + $PPArr;
		$fields["StartDatePropertyID"]["Options"] = array("" => "") + $PPArr;

		$eliteManager = new TTableLinksFieldManager();
		$eliteManager->Fields = array(
			"EliteLevelID" => array(
				"Type" => "integer",
				"Options" => array("" => "") + $ELArr,
				"Required" => true,
				"Caption" => "Elite Level",
			),
			"Value" => array(
				"Type" => "string",
				"Size" => 20,
				"Required" => true,
				"Caption" => "Value",
			),
		);
		$eliteManager->UniqueFields = array("EliteLevelID");
		$eliteManager->TableName = "EliteLevelValue";
		$eliteManager->AutoSave = true;
		$eliteManager->CanEdit = true;
		ArrayInsert($fields, "EndDay", true, array(
			'EliteValues' => array(
				'Type' => 'string',
				'Manager' => $eliteManager,
			)
		));
		return $fields;
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm( $form );
		if (isset($_GET['ProviderPropertyID']) && ( intval( ArrayVal( $_GET, 'ID' ) ) == 0 ) )
			$form->Fields["ProviderPropertyID"]["Value"] = intval( $_GET['ProviderPropertyID'] );
		$form->Uniques[] = array(
			"Fields" => array( "ProviderPropertyID"),
			"ErrorMessage" => "Elite levels depending on this property already exist",
		);
		if(ArrayVal($_SERVER, 'REMOTE_USER') == 'points'){
			$form->SubmitButtonCaption = "Cancel";
			$form->ReadOnly = true;
		}
	}
}

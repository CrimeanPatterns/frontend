<?php

require_once(__DIR__ . "/ProviderPhone.php");

class TEliteLevelSchema extends TBaseSchema {
	
	function TEliteLevelSchema(){
		parent::TBaseSchema();
		$this->TableName = "EliteLevel";
		$this->ListClass = "EliteStatusValueList";
		
		$this->Fields = array(
			"ProviderID" => array(
			    "Caption" => "Provider",
				"Type" => "integer",
			    "Required" => true,
				"Options" => array("" => "") + SQLToArray("select ProviderID, DisplayName from Provider order by DisplayName", "ProviderID", "DisplayName"),
			),
			"Rank" => array(
			    "Type" => "integer",
			    "Size" => 20,
			    "Required" => true,
                'FilterField' => '`Rank`',
			),
            "ByDefault" => array(
                "Type" => "boolean",
                "Required" => true,
            ),
			"Name" => array(
			    "Type" => "string",
			    "Size" => 50,
			    "Required" => true,
			),
			"Description" => array(
				"Type" => "string",
				"InputType" => "textarea",
				"Size" => 2000,
				"InputAttributes" => "style='width: 400px;'",
			),
			"NoElitePhone" => array(
			    "Type" => "integer",
				"Options" => array(
					"" => "",
					"1" => "No phone",
				)
			),
			"AllianceEliteLevelID" => array(
			    "Caption" => "Alliance elite level",
				"Type" => "integer",
				"Options" => array("" => "") + SQLToArray("select
				 	ael.AllianceEliteLevelID,
					concat(al.Name, ' - ', ael.Name) as Name
				from
					Alliance al
					join AllianceEliteLevel ael on al.AllianceID = ael.AllianceID
				order by
					concat(al.Name, ' - ', ael.Name)", "AllianceEliteLevelID", "Name"),
			),
			"Phones" => array(
				"Type" => "string",
				"Database" => false,
			),
		);
	}

    function TuneList(&$list)
    {
        parent::TuneList($list);
        $list->SQL = 'SELECT * FROM EliteLevel';
    }
	
	function GetListFields(){
		$arFields = parent::GetListFields();
		unset($arFields['Description']);

		ArrayInsert($arFields, "ProviderID", true, array(
			"ValueText" => array(
				"Caption" => "Keywords",
				"Type" => "string",
				"Size" => 40,
				"Database" => false,
			)
		));
		
		return $arFields;
	}
	
	function GetFormFields(){
		$arFields = parent::GetFormFields();
		$objManager = new TTableLinksFieldManager();
		$objManager->TableName = "TextEliteLevel";
		$objManager->Fields = array(
			"ValueText" => array(
				"Caption" => "Keyword",
				"Type" => "string",
				"Cols" => 60,
				"Size" => 250,
				"Required" => true,
			),
		);
		
		ArrayInsert($arFields, 'ProviderID', true, array('Keyword'=> array('Manager'=>$objManager) ));

		$arFields['Phones']['InputType'] = 'html';
		$eliteLevelId = intval(ArrayVal($_GET, 'ID'));
		$providerId = Lookup("EliteLevel", "EliteLevelID", "ProviderID", $eliteLevelId);
		$arFields['Phones']['HTML'] = TProviderPhoneSchema::getPhonesLink($providerId, $eliteLevelId);

		$arFields['NoElitePhone']['Caption'] = 'No dedicated elite number exists';

		return $arFields;
	}
	
	function TuneForm(\TBaseForm $form){
		parent::TuneForm( $form );
		if( isset( $_GET['ProviderID'] ) && ( intval( ArrayVal( $_GET, 'ID' ) ) == 0 ) ){
			$form->Fields["ProviderID"]["Value"] = intval( $_GET['ProviderID'] );
		}
	}
	
}

?>

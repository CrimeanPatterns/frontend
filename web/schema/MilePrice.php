<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 05.08.15
 * Time: 15:44
 */

require_once __DIR__."/../lib/classes/TBaseSchema.php";

class TMilePriceSchema extends TBaseSchema {

    function TMilePriceSchema(){
        global $arProviderKind;
        parent::TBaseSchema();
        $this->TableName = "MilePrice";
        $this->Fields = array(
            "MilePriceID" => array(
                "Caption" => "id",
                "Type" => "integer",
                "Required" => True,
                "InputAttributes" => " readonly",
                "filterWidth" => 30,
                "InplaceEdit" => False,
            ),
            "ProviderID" => array(
                "Caption" => "Provider",
                "Type" => "integer",
                "Required" => true,
                "Options" => array("" => "All providers") + SQLToArray("select ProviderID, DisplayName from Provider order by DisplayName", "ProviderID", "DisplayName"),
                "InplaceEdit" => False,
            ),
            "NumberOfMiles" => array(
                "Type" => "integer",
                "Required" => True,
                "InplaceEdit" => False,
            ),
            "Price" => array(
                "Type" => "float",
                "Required" => True,
                "InplaceEdit" => False,
            ),
            "CurrencyID" => array(
                "Caption" => "Currency",
                "Type" => "integer",
                "Required" => true,
                "Options" => array("" => "All currencies") + SQLToArray("select `CurrencyID`, `Name` from `Currency` where `code` is not null order by `Name`", "CurrencyID", "Name"),
                "InplaceEdit" => False,
            ),
        );
    }

    function GetFormFields(){
        $fields = parent::GetFormFields();
        unset($fields['MilePriceID']);
        return $fields;
    }

	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		$form->OnSave = array($this, "formSaved", &$form);
	}

	function formSaved($objForm){
		require_once 'Provider.php';
		TProviderSchema::triggerDatabaseUpdate();
	}
}

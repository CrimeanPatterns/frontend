<?php

// #7186

require_once(__DIR__.'/../lib/classes/TBaseSchema.php');

class TInAppPurchaseSchema extends TBaseSchema
{
    function __construct(){
        parent::TBaseSchema();
        $this->TableName = "InAppPurchase";
        $this->Fields = array(
            "InAppPurchaseID" => array(
                "Caption" => "InAppPurchaseID",
                "Type" => "integer",
                "Required" => True,
            ),
            "StartDate" => array(
                "Caption" => "StartDate",
                "Type" => "string",
                "Required" => True,
            ),
            "UserID" => array(
                "Caption" => "UserID",
                "Type" => "integer",
                "Required" => True,
            ),
            "EndDate" => array(
                "Caption" => "EndDate",
                "Type" => "string",
            ),
            "UserAgent" => array(
                "Caption" => "UserAgent",
                "Type" => "string",
            ),
        );
        $this->DefaultSort = 'UserID';
    }

    function TuneForm(\TBaseForm $form){
        parent::TuneForm($form);
        $form->ReadOnly = true;
    }
}
?>

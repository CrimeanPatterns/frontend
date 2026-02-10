<?php

// #7186

require_once(__DIR__.'/../lib/classes/TBaseSchema.php');

class TOfferBanSchema extends TBaseSchema
{
    function __construct(){
        parent::TBaseSchema();
        $this->TableName = "OfferBan";
        $this->Fields = array(
            "UserID" => array(
                "Caption" => "UserID",
                "Type" => "integer",
                "Required" => True,
            ),
            "Reason" => array(
                "Caption" => "Reason",
                "Type" => "string",
            ),
        );
        $this->DefaultSort = 'UserID';
    }

    function TuneForm(\TBaseForm $form){
        parent::TuneForm($form);
        $form->Uniques[] = array(
            "Fields" => array("UserID"),
            "ErrorMessage" => "This user has already been added",
        );
    }
}
?>

<?php

// #6873

require_once(__DIR__.'/../lib/classes/TBaseSchema.php');

class TAdIncomeSchema extends TBaseSchema
{
    function __construct(){
        ?>
            <style>
                #ui-datepicker-div
                {
                    display: none;
                }
            </style>
        <?
        parent::TBaseSchema();
        $this->TableName = "AdIncome";
        $this->Fields = array(
            "PayDate" => array(
                "Caption" => "Pay Date",
                "Type" => "date",
                "Required" => True,
                "Sort" => "PayDate desc",
            ),
            "Income" => array(
                "Caption" => "Income",
                "Type" => "integer",
            ),
        );
        $this->DefaultSort = 'PayDate';
    }
}
?>
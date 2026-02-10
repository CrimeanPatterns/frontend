<?php

// #5857

require_once(__DIR__.'/../lib/classes/TBaseSchema.php');

class TOfferSchema extends TBaseSchema
{

    static $displayTypes = array(0 => 'Web Page', 1 => 'Popup');

	static function getShowPeriod(){
		// minutes, use x*24*60 for days, 1 for testing
		// return 1;
		return 1*24*60;
		}
	
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
		$this->ListClass = "OfferList";
		$this->TableName = "Offer";
		$this->Fields = array(
        	"OfferID" => array(
	        	"Caption" => "OfferID",
		        "Type" => "integer",
				"InputAttributes" => "readonly",
	            "Sort" => "OfferID DESC",
	        ),
			"Name" => array(
                "Type" => "string",
				"Size" => 250,
				"Required" => True,
                "InputAttributes" => "style='width: 300px;'",
			),
            "Code" => array(
				"Type" => "string",
				"Size" => 60,
				"Required" => True,
                "InputAttributes" => "style='width: 300px;'",
                "RegExp" => '/^[a-z0-9]*$/',
			),                        
            "ApplyURL" => array(
	        	"Caption" => "URL",
                "Type" => "string",
                "Size" => 512,
                "Cols" => 50,
                "HTML" => true,
                "Required" => True,
            ),
            "Description" => array(
				"Type" => "string",
				"InputType" => "textarea",
				"Size" => 4000,
                "InputAttributes" => "style='width: 300px;'",
			),                        
        	"RemindMeDays" => array(
	        	"Caption" => "Remind Me Days",
		        "Type" => "integer",
                "Required" => True,
	        ),
            "Enabled" => array(
				"Type" => "boolean",
				"Required" => True,
                "InputType" => "checkbox",
                "Value" => "1"
			),
            "Priority" => array(
                "Type" => "integer",
                "Required" => True,
                "Value" => "0"
            ),
            "Kind" => array(
                "Type" => "integer",
                "Required" => True,
                "Value" => "0"
            ),
            "DisplayType" => array(
                "Type" => "integer",
                "Options" => self::$displayTypes,
                "Required" => True,
            ),
            "MaxShows" => array(
                "Caption" => "MaxShows",
                "Type" => "integer",
                "Value" => "3",
            ),
            "ShowsCount" => array(
                "Caption" => "ShowsCount",
                "Type" => "integer",
                "InputAttributes" => "readonly",
            ),
            'ShowUntilDate' => array(
                "Caption" 	=> "Show Until Date",
                "Type" 		=> "date",
            ),
        );
        $this->DefaultSort = 'OfferID'; 
	}

	function GetFormFields(){
		$fields = parent::GetFormFields();
		unset($fields['OfferID'], $fields['ShowsCount']);
        // if MaxShows is null it will be set to 0, otherwise to default 3
        $fields['MaxShows']['Value'] = 0;

		return $fields;
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		$form->Uniques = array(
		  	array(
		    	"Fields" => array("Name"),
		    	"ErrorMessage" => "This name already exists. Please choose another name."
		  	),
		  	array(
		    	"Fields" => array("Code"),
		    	"ErrorMessage" => "This code already exists. Please choose another code."
		  	)
		);
        if (ArrayVal($_GET, 'ID', 0) == 0)
        	$form->SQLParams["CreationDate"] = "now()";
        $form->OnCheck = array(&$this, "FormCheck", &$form);
    }

	function FormCheck($form){
        if ($form->Fields['MaxShows']['Value'] < 1)
            $form->Fields['MaxShows']['Value'] = null;
    }

    function GetListFields(){
		$fields = parent::GetListFields();
		unset($fields['RemindMeDays']);
		unset($fields['Description']);
		unset($fields['Description']);
		unset($fields['DisplayType']);
		unset($fields['ApplyURL']);
        return $fields;
	}

}
?>

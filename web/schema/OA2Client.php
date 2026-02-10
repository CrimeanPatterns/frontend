<?php

require_once(__DIR__ . "/ProviderPhone.php");

class TOA2ClientSchema extends TBaseSchema {
	
	function TOA2ClientSchema(){
		parent::TBaseSchema();
		$this->TableName = "OA2Client";

		$this->Fields = array(
			"Login" => array(
				"Type" => "string",
				"Size" => 40,
			    "Required" => true,
			),
			"Pass" => array(
				"Type" => "string",
				"Size" => 40,
			    "Required" => true,
			),
			"RedirectURL" => array(
			    "Type" => "string",
			    "Size" => 200,
			    "Required" => true,
				"Caption" => "Redirect URL",
			),
			"AccessTokenLifetime" => array(
			    "Type" => "integer",
			    "Required" => true,
				"Value" => "3600",
				"Note" => "seconds",
			),
		);
	}
	
	function TuneForm(\TBaseForm $form){
		parent::TuneForm( $form );
		$form->Uniques[] = array(
			"Fields" => array( "Login" ),
			"ErrorMessage" => "Client with this Login already exists"
		);
	}
	
}

?>

<?

class TMediaContactSchema extends TBaseSchema {

	function __construct() {
		parent::TBaseSchema();
		$this->TableName = "MediaContact";
		$this->Fields = array(
			"MediaContactID" => array(
				"Caption" => "id",
				"Type" => "integer",
				"Required" => true,
				"InputAttributes" => " readonly",
				"filterWidth" => 30,
			),
			'Name' => array(
				"Type" 		=> "string",
				"Required"  => true,
				"Size" => 4000,
				"InputAttributes" => "style='width: 300px;'",
			),
			'URL' => array(
				"Caption" => "URL",
				"Type" => "string",
				"Required" => false,
				"Size" => 1000,
				"InputAttributes" => "style='width: 300px;'",
			),
			'FirstName' => array(
				"Caption" => "First Name",
				"Type" => "string",
				"Required" => false,
				"Size" => 30,
			),
			'LastName' => array(
				"Caption" => "Last Name",
				"Type" => "string",
				"Required" => false,
				"Size" => 50,
			),
			'Email' => array(
				"Type" => "string",
				"Required" => false,
				"Size" => 80,
			),
			'AltContactMethod' => array(
				"Caption" => "AltContactMethod",
				"Type" => "string",
				"InputType" => "textarea",
				"Required" => false,
				"Size" => 4000,
			),
			'LastContactedBy' => array(
				"Caption" 	=> "Last Contacted By",
				"Type" => "string",
				"Required" => false,
				"Size" => 250,
				"InputAttributes" => "style='width: 300px;'",
			),
			'LastContactDate' => array(
				"Caption" 	=> "Last Contact Date",
				"Type" => "date",
				"IncludeTime" => true,
				"Required" => false,
			),
			'Responses' => array(
				"Type" => "string",
				"InputType" => "textarea",
				"Required" => false,
				"Size" => 4000,
			),
			'Comments' => array(
				"Type" => "string",
				"InputType" => "textarea",
				"Required" => false,
				"Size" => 4000,
			),
			'NDR' => array(
				"Caption" => "Email status",
				"Type" => "string",
				"Options" => array(
					EMAIL_UNVERIFIED => 'Email unverified',
					EMAIL_VERIFIED => 'Email verified',
					EMAIL_NDR => 'Email ndr',
				),
				"Required" => false,
			),
			'Unsubscribed' => array(
				"Type" => "boolean",
				"Required" => false,
			),
		);
	}

	function TuneList( &$list ) {
		parent::TuneList($list);
		$list->AllowDeletes = true;
		$list->ReadOnly = false;
		$list->ShowEditors = true;
		$list->ShowFilters = true;
		$list->MultiEdit = true;
		$list->ShowImport = true;
		$list->ShowExport = true;
		$list->CanAdd = true;
		$list->PageSizes['1000'] = '1000';
		$list->PageSize = 1000;

	}

	function GetListFields(){
		$fields = parent::GetListFields();
		unset($fields['Comments']);
		unset($fields['Responses']);
		unset($fields['LastContactedBy']);
		unset($fields['MediaContactID']);
		return $fields;
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		unset($form->Fields['MediaContactID']);

		$form->Uniques[] = array(
			"Fields" => array( "Email" ),
			"ErrorMessage" => "This email already exists",
			"AllowNulls" => true,
		);
	}

	function Delete(){}


}
?>

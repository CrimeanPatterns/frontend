<?

class TAbRequestStatusSchema extends TBaseSchema
{
	function __construct()
	{
		global $Config;
		parent::TBaseSchema();
		$this->TableName = "AbRequestStatus";
		$this->KeyField = $this->TableName . "ID";
		$this->Description = array("Booking", "Internal Statuses");
		$this->DefaultSort = "BookerID";
		$this->Fields = array(
			$this->KeyField => array(
				"Caption" => "id",
				"Type" => "integer",
				"Size" => 250,
			),
			"BookerID" => array(
				"Caption" => "Booker",
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"InputType" => "select",
				"Required" => True,
				"Options" => SQLToArray("select u.UserID, u.Login from Usr u, AbBookerInfo i WHERE u.AccountLevel = ". ACCOUNT_LEVEL_BUSINESS . " and i.UserID = u.UserID ORDER BY Login;","UserID","Login"),
			),
			"Status" => array(
				"Caption"         => "Status",
				"Type"            => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Required"        => true,
				"Size"            => 250,
				"Note"            => "Description of this internal status"
			),
			"SortIndex" => array(
				"Caption"         => "Sort Index",
				"Type"            => "string",
				"InputAttributes" => "style=\"width: 100px;\"",
				"Required"        => true,
				"Size"            => 11,
				"Note"            => "Sort index"
			),
			"TextColor" => array(
				"Caption"         => "Text color",
				"Type"            => "string",
				"InputAttributes" => "style=\"width: 100px;\"",
				"Required"        => true,
				"Size"            => 6,
				"Note"            => "Text color"
			),
			"BgColor" => array(
				"Caption"         => "Background color",
				"Type"            => "string",
				"InputAttributes" => "style=\"width: 100px;\"",
				"Required"        => true,
				"Size"            => 6,
				"Note"            => "Background color"
			),
        );
    }

	function GetListFields()
	{
		$arFields = parent::GetListFields();
		return $arFields;
	}

	function TuneList( &$list )
	{
		/* @var $list TBaseList */
		parent::TuneList( $list );

		$list->KeyField = $this->KeyField;
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm( $form );
		$form->KeyField = $this->KeyField;
	}

	function GetFormFields()
	{
		$arFields = $this->Fields;
		unset($arFields[$this->KeyField]);
		return $arFields;
	}
}

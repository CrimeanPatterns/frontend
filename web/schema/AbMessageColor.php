<?

class TAbMessageColorSchema extends TBaseSchema
{
	function __construct()
	{
		global $Config;
		parent::TBaseSchema();
		$this->TableName = "AbMessageColor";
		$this->KeyField = $this->TableName . "ID";
		$this->Description = array("Booking", "Message Colors");
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
			"Color" => array(
				"Caption" => "Color",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"Required" => True,
				"Options" => array('purple' => 'purple', 'light-green' => 'light-green', 'regular-green' => 'regular-green', 'darker-green' => 'darker-green', 'light-orange' => 'light-orange', 'regular-orange' => 'regular-orange', 'dark-orange' => 'dark-orange', 'red' => 'red', 'blue' => 'blue')
			),
			"Description" => array(
				"Caption"         => "Description",
				"Type"            => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Required"        => true,
				"Size"            => 250,
				"Note"            => "Description of this internal message"
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

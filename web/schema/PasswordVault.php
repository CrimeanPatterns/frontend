<?

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\Common\PasswordCrypt\PasswordEncryptor;

class TPasswordVaultForm extends TForm{

	function SQLValue($fieldName, $field = NULL){
		if($fieldName == "Pass"){
			if(empty($field))
				$field = $this->Fields[$fieldName];
			return "'".addslashes(getSymfonyContainer()->get(PasswordEncryptor::class)->encrypt($field['Value']))."'";
		}
		else
			return parent::SQLValue($fieldName, $field);
	}

	function SetFieldValues($values){
		if(isset($values['Pass']))
			$values['Pass'] = getSymfonyContainer()->get(PasswordDecryptor::class)->decrypt($values['Pass']);
		parent::SetFieldValues($values);
	}
}

class TPasswordVaultSchema extends TBaseSchema{

	function TPasswordVaultSchema(){
		parent::TBaseSchema();
		$this->TableName = "PasswordVault";
		$this->FormClass = "TPasswordVaultForm";
		$this->Fields = array(
			"PasswordVaultID" => array(
				"Type" => "integer",
				"Caption" => "ID",
				"filterWidth" => 20,
				"InplaceEdit" => false,
			),
			"CreationDate" => array(
				"Type" => "date",
				"FilterField" => "pv.CreationDate",
				"InplaceEdit" => false,
			),
			"AccountID" => array(
				"Type" => "integer",
				"RequiredGroup" => "login",
				"Caption" => "Account ID",
				"FilterField" => "pv.AccountID",
				"InplaceEdit" => false,
			),
			"Partner" => array(
				"Type" => "string",
				"Size" => 20,
				"filterWidth" => 10,
				"InplaceEdit" => false,
			),
			"Login" => array(
				"Type" => "string",
				"Size" => 80,
				"RequiredGroup" => "login",
				"filterWidth" => 40,
				"FilterField" => "coalesce(a.Login, pv.Login)",
				"HTML" => true,
				"InplaceEdit" => false,
			),
			"Login2" => array(
				"Type" => "string",
				"Size" => 120,
				"RequiredGroup" => "login",
				"FilterField" => "coalesce(a.Login2, pv.Login2)",
				"filterWidth" => 30,
				"HTML" => true,
				"InplaceEdit" => false,
			),
			"Pass" => array(
				"Type" => "string",
				"Size" => 250,
				"RequiredGroup" => "login",
				"HTML" => true,
				"InplaceEdit" => false,
			),
			"IssueID" => array(
				"Type" => "integer",
				"Caption" => "Issue #",
				"filterWidth" => 20,
				"InplaceEdit" => false,
			),
			"ExpirationDate" => array(
				"Type" => "date",
				"FilterField" => "pv.ExpirationDate",
				"InplaceEdit" => false,
			),
			"Approved" => array(
				"Type" => "boolean",
				"FilterField" => "pv.Approved",
				"InplaceEdit" => true,
				"Value" => "1",
			),
		);
		$this->ListClass = "TPasswordVaultList";
	}

	static function GetUsers($groupName = 'Staff', $field = "concat(u.FirstName, ' ', u.LastName)"){
		return SQLToArray("select u.UserID, $field as UserName
					from Usr u
					join GroupUserLink gl on u.UserID = gl.UserID
					join SiteGroup g on gl.SiteGroupID = g.SiteGroupID
					where g.GroupName = '".addslashes($groupName)."' order by userName", "UserID", "UserName");
	}

	static function canRequestCC($userId){
		$ccStaff = TPasswordVaultSchema::GetUsers('Credit card passwords', "u.UserID");
		return in_array($userId, $ccStaff);
	}

	function GetFormFields(){
		$fields = parent::GetFormFields();
		unset($fields['CreationDate']);
		unset($fields['PasswordVaultID']);
//		if(isset($_GET['UserID']))
//			$fields['UserID']['Value'] = $_GET['UserID'];
		if(isset($_GET['AccountID']))
			$fields['AccountID']['Value'] = $_GET['AccountID'];
		$fields['ExpirationDate']['Value'] = date(DATE_FORMAT, strtotime("+1 month"));
		$manager = new TCheckBoxLinksFieldManager();
		$manager->TableName = "PasswordVaultUser";
		$manager->ValueField = "UserID";
		$manager->Checkboxes = $this->GetUsers();
		ArrayInsert($fields, "Pass", true, array(
			"Users" => array(
				"Type" => "string",
				"Manager" => $manager,
			)
		));
		return $fields;
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		$form->Uniques[] = array(
			"Fields" => array("AccountID", "Login", "Login2"),
			"ErrorMessage" => "Record with this account and login already exists",
		);
		if(ArrayVal($_GET, 'ID', 0) == 0){
			$form->SQLParams["CreationDate"] = "now()";
			$form->SQLParams["UserID"] = 7;
		}
		$form->OnCheck = array($this, "CheckForm", $form);
	}

	function CheckForm($objForm){
		if(!empty($objForm->Fields['AccountID']['Value'])){
			$accountId = intval($objForm->Fields['AccountID']['Value']);
			$q = new TQuery("select ProviderID from Account where AccountID = $accountId");
			if($q->EOF)
				return "Account not found";
			if($q->Fields['ProviderID'] == '')
				return "This is custom account";
		}
		if(!isset($objForm->Fields['Partner']['Value']))
			$objForm->Fields['Partner']['Value'] = '';
		return null;
	}

	function GetListFields(){
		$fields = parent::GetListFields();
		unset($fields['Pass']);
		unset($fields['CreationDate']);
		return $fields;
	}

	/**
	 * @param  $list TBaseList
	 * @return void
	 */
	function TuneList(&$list){
		parent::TuneList($list);
		$list->ShowBack = true;
		$list->MultiEdit = true;
//		$objList->InplaceEdit = true;
	}
}

<?

use AwardWallet\MainBundle\Security\StringSanitizer;

require_once __DIR__ . "/../manager/passwordVault/common.php";

class TPasswordVaultList extends TBaseList{

	private $host;

	function __construct($table, $fields, $defaultSort){
		ArrayInsert($fields, "AccountID", false, array(
			"UserName" => array(
				"Type" => "string",
				"Caption" => "Request author",
				"InplaceEdit" => false,
				"FilterField" => "concat(u.FirstName,' ',	u.LastName)",
				"filterWidth" => "50",
			),
			"DisplayName" => array(
				"Type" => "string",
				"Size" => 40,
				"InplaceEdit" => false,
			),
			"UserID" => array(
				"Type" => "integer",
				"Caption" => "User ID",
				"InplaceEdit" => false,
				"FilterField" => "a.UserID"
			),
		));
		parent::__construct($table, $fields, $defaultSort);
		$this->SQL = "select
			pv.CreationDate,
			pv.PasswordVaultID,
			pv.Partner,
			coalesce(a.Login, pv.Login) as Login,
			coalesce(a.Login2, pv.Login2) as Login2,
			pv.AccountID,
			pv.IssueID,
			pv.ExpirationDate,
			a.ProviderID,
			a.UserID,
			p.DisplayName,
			pv.Approved,
			u.Email,
			concat(u.FirstName,' ',	u.LastName) as UserName
		from
			PasswordVault pv
			join Usr u on pv.UserID = u.UserID
			left outer join Account a on pv.AccountID = a.AccountID
			left outer join Provider p on a.ProviderID = p.ProviderID or pv.ProviderID = p.ProviderID";

		$this->host = getSymfonyContainer()->getParameter("host");
	}

	function FormatFields($output = "html"){
		parent::FormatFields($output);
//		$this->Query->Fields['Users'] = implode(", ", SQLToSimpleArray("select concat(u.FirstName, ' ', u.LastName)
//		from Usr u join PasswordVaultUser pvu on u.UserID = pvu.UserID
//		where pvu.PasswordVaultID = {$this->Query->Fields['PasswordVaultID']}
//		order by u.Login", "Login"));
		if(!empty($this->Query->Fields['IssueID']))
			$this->Query->Fields['IssueID'] = "<a href=\"http://redmine.itlogy.com/issues/{$this->Query->Fields['IssueID']}\" target=\"_blank\">#{$this->Query->Fields['IssueID']}</a>";
		if(!empty($this->Query->Fields['UserID']))
			$this->Query->Fields['UserID'] = "<a target=\"blank\" href='/manager/impersonate?UserID={$this->Query->Fields['UserID']}&Goto=/'>{$this->Query->Fields['UserID']}</a>";
		$this->Query->Fields['Login'] = htmlspecialchars($this->Query->Fields['Login']);
		$this->Query->Fields['Login2'] = htmlspecialchars($this->Query->Fields['Login2']);
	}

	function GetEditLinks(){
		$links = parent::GetEditLinks();
		$links .= " | <a href='//{$this->host}/manager/passwordVault/log.php?PasswordVaultID={$this->Query->Fields['PasswordVaultID']}'>Logs</a>";
		$links .= " | <a href='//{$this->host}/manager/passwordVault/get.php?ID={$this->Query->Fields['PasswordVaultID']}'>Share</a>";
		return $links;
	}

	function SaveInplaceFormRow(&$arRow){
		parent::SaveInplaceFormRow($arRow);
		foreach($this->InplaceForm->Fields as $sField => $arField)
			if(isset($arField["KeyField"]) && ($arField["KeyField"] == $arRow[$this->KeyField]) && isset($arField["SQLValue"]))
				if(($arField["OldValue"] != $arField["Value"]) && ($arField["Value"] == '1'))
					notifyApproved($arField["KeyField"]);
	}

	// draw buttons
	function DrawButtons($closeTable=true)
	{
		echo "<table cellspacing=0 cellpadding=0 border=0 width='100%'><tr><td style='text-align: left; border: none;'>";
		if( !$this->Query->IsEmpty && $this->MultiEdit ){
#			echo "<input type='Checkbox' onclick=\"javascript:selectAll(this)\">";
			echo "<input type=checkbox value=\"1\" onclick=\"selectCheckBoxes( this.form, 'sel', this.checked )\"> Select All";
			echo "</td><td align='right' style='border: none;'>";
			echo "<input class='button' type=button value=\"Approve selected\" onclick=\"this.form.action.value = 'approve'; form.submit();\"> ";
			if( $this->InplaceEdit )
				echo "<input class='button' type=button value=\"Save changes\" onclick=\"if(CheckForm(this.form)){ this.form.action.value = 'update'; form.submit();}\"> ";
			if( $this->AllowDeletes )
				echo "<input class='button' type=button value=\"Delete\" onclick=\"DeleteSelectedFromList( this.form )\"> ";
		}
		if( $this->CanAdd && !$this->ReadOnly )
			echo "<input class='button' type=button value=\"Add New\" onclick=\"location.href = 'edit.php?ID=0{$this->URLParamsString}'\"> ";
		if( $this->ShowExport && (isset( $this->Schema ) || isset($this->ExportName) ) ){
			if( $this->Schema->Name == "" )
				DieTrace("Schema name required for export. Did you forget to call TBaseSchema()?");
			echo "<input class='button' type=button value=\"Export\" onclick=\"location.href = 'export.php?" . StringSanitizer::encodeHtmlEntities($_SERVER['QUERY_STRING']) . "'\"> ";
		}
		if( $this->ShowImport && !$this->ReadOnly && isset( $this->Schema ) )
			echo "<input class='button' type=button value=\"Import\" onclick=\"location.href = 'import.php?Schema={$this->Schema->Name}'\"> ";
		if($this->ShowBack && isset($_GET['BackTo']))
			echo "<input class='button' type=button value=\"Go Back\" onclick=\"location.href = '{$_GET['BackTo']}'\"> ";
		if($closeTable)
			echo "</td></tr></table>";
	}

	function ProcessAction($action, $ids){
		switch($action){
			case "approve";
				foreach($ids as $id){
					$q = new TQuery("select Approved from PasswordVault where PasswordVaultID = $id");
					if($q->Fields['Approved'] == '0'){
						approveAndExtend($id);
						notifyApproved($id);
					}
				}
				break;
			default:
				parent::ProcessAction($action, $ids);
		}
	}

}

<?
require_once __DIR__ . "/../schema/ProviderPhone.php";
require_once __DIR__ . "/../schema/Provider.php";

class ProviderAdminList extends TBaseList{

	function FormatFields($output = "html"){
		parent::FormatFields($output);
        if (null === $this->Query->Fields['CanRetrievePassword']) {
            $this->Query->Fields['CanRetrievePassword'] = '-';
        }
		if($output == "html"){
			$this->Query->Fields["DeepLinking"] = "<a href='/manager/testDeepLinking.php?ProviderID={$this->Query->Fields['ProviderID']}&Using=service'>{$this->Query->Fields["DeepLinking"]}</a>";
			$this->Query->Fields["AutoLogin"] = "<a href='/manager/testDeepLinking.php?ProviderID={$this->Query->Fields['ProviderID']}&Using=awardwallet'>{$this->Query->Fields["AutoLogin"]}</a>";
			$url = $this->Query->Fields['Site'];
			$this->Query->Fields['Site'] = preg_replace("/https?:\/\//ims", "", $this->Query->Fields['Site']);
			if(strlen($this->Query->Fields['Site']) > 20)
				$this->Query->Fields['Site'] = substr($this->Query->Fields['Site'], 0, 20)."..";
			$this->Query->Fields['Site'] = "<a href='{$url}' target='_blank'>{$this->Query->Fields['Site']}</a>";
			$this->Query->Fields['Phones'] = TProviderPhoneSchema::getPhonesLink($this->Query->Fields['ProviderID'], null);
		}
	}

	function DrawButtonsInternal(){
		$triggers = parent::DrawButtonsInternal();
        echo "<input id=\"RemoveAccountsId\" class='button' type=button value=\"Remove Accounts\" onclick=\"RemoveAccounts( this.form )\"> ";
		echo "<input id=\"UpdateDatabaseId\" class='button' type=button value=\"Update all servers\" onclick=\"{ this.form.action.value = 'updateDatabase'; form.submit();}\"> ";
		echo "<input id=\"UpdateDatabaseTest\" class='button' type=button value=\"Update test\" onclick=\"{ this.form.action.value = 'updateDatabaseTest'; form.submit();}\"> ";
        $triggers[] = array('RemoveAccountsId', 'Remove Accounts');
		$triggers[] = array('UpdateDatabaseId', 'Update all servers');
		$triggers[] = array('UpdateDatabaseTest', 'Update test');
		return $triggers;
	}

	function ProcessAction($action, $ids){
		switch($action){
			case "updateDatabase";
				TProviderSchema::triggerDatabaseUpdate();
				echo "Triggered database update";
				break;
			case "updateDatabaseTest";
				TProviderSchema::triggerDatabaseUpdate();
				echo "Triggered test database update";
				break;
            case "removeAccounts":
                if ( isset( $this->ExternalDelete ) ) {
                    $data = array( "Table" => $this->Table, "ID" => implode( ",", $ids ), "BackTo" => $_SERVER['REQUEST_URI'] );
                    if (isset($this->Schema))
                        $data['Schema'] = $this->Schema->Name;
                    PostRedirect( 'removeAccounts.php', $data );
                }
			default:
				parent::ProcessAction($action, $ids);
		}
	}

    function SaveInplaceForm($sSQL){
	    parent::SaveInplaceForm($sSQL);
        TProviderSchema::triggerDatabaseUpdate();
    }

    function AddFilters($sSQL)
    {
        $sSQL = parent::AddFilters($sSQL);
        $sSQL = str_replace('CanRetrievePassword = ' . TProviderSchema::SQL_BOOL_NOT_SET, 'CanRetrievePassword IS NULL ', $sSQL);
        return $sSQL;
    }

    function GetFilterFields()
    {
        $filterFiedlds = parent::GetFilterFields();
        $filterFiedlds['CanRetrievePassword']['Options'][''] = '';
        $filterFiedlds['CanRetrievePassword']['Options'][TProviderSchema::SQL_BOOL_NOT_SET] = 'Not set';
        return $filterFiedlds;
    }


    function GetEditLinks(){
		$links = parent::GetEditLinks();
		$links .= " | <a href=\"/manager/list.php?Schema=EliteLevelProgress&EliteLevelProgressID=&Provider=".$this->Query->Fields["ProviderID"]."&ProviderPropertyID=&StartDatePropertyID=\">ELP</a>";
		$links .= ' | <a href="/manager/list.php?Schema=RewardsTransfer&SourceProviderID='.$this->Query->Fields['ProviderID'].'">Rates</a>';
		return $links;
	}

}

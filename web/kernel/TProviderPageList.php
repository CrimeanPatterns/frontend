<?

class TProviderPageList extends TBaseList{

	function __construct($table, $fields, $defaultSort){
		parent::__construct($table, $fields, $defaultSort);
		$this->SQL = "select * from ProviderPage";
	}

	function FormatFields($output = "html"){
		parent::FormatFields($output);
		$arFields = &$this->Query->Fields;
		$arFields["PageName"] = "<a target=\"_blank\" href=\"".htmlspecialchars($arFields['PageURL'])."\">{$arFields['PageName']}</a>";
	}

	function GetEditLinks(){
		$arFields = &$this->Query->Fields;
		$result = parent::GetEditLinks() . " | <a href=\"/admin/checkProviderPage.php?ID={$arFields['ProviderPageID']}\">Check</a>";
		if($this->OriginalFields['Status'] >= PAGE_STATUS_DIFF){
			$result .= " | <a href=\"/admin/commitProviderPage.php?ID={$arFields['ProviderPageID']}\">Commit</a>";
			$result .= " | <a href=\"/admin/diffProviderPage.php?ID={$arFields['ProviderPageID']}\">Diff</a>";
		}
		return $result;
	}
}

?>

<?

require_once(__DIR__ . "/../lib/schema/News.php");

class THomeNewsSchema extends TNewsSchema
{
	var $newsNumber = 1;
	var $newsLimit = 2;
	function THomeNewsSchema(){
		parent::TNewsSchema();
		unset($this->Fields['FullName']);
		unset($this->Fields['Email']);
		unset($this->Fields['Title']);
		unset($this->Fields['Email']);
		unset($this->Fields['Rank']);
		unset($this->Fields['NewsPhoto']);
	}

	function GetFormFields(){
		$arFields = $this->Fields;
		unset($arFields['NewsID']);
		return $arFields;
	}
	
	function TuneList(&$list){
		parent::TuneList( $list );
		if(!$this->Admin)
			$list->Limit = $this->newsLimit;
	}
}
?>

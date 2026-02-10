<?

define('PAGE_STATUS_UNCHECKED', 0);
define('PAGE_STATUS_MATCH', 1);
define('PAGE_STATUS_DIFF', 2);
define('PAGE_STATUS_MISSING_START', 3);
define('PAGE_STATUS_MISSING_END', 4);

global $arPageStatus;
$arPageStatus = array(
	PAGE_STATUS_UNCHECKED => "Unchecked",
	PAGE_STATUS_MATCH => "No changes",
	PAGE_STATUS_DIFF => "Changed",
	PAGE_STATUS_MISSING_START => "Missing start",
	PAGE_STATUS_MISSING_END => "Missing end",
);

class TProviderPageSchema extends TBaseSchema
{
	function TProviderPageSchema(){
		global $arPageStatus;
		parent::TBaseSchema();
		$this->TableName = "ProviderPage";
		$this->Fields = array(
			"ProviderID" => array(
			    "Type" => "integer",
				"Caption" => "Provider",
			    "Required" => True,
			    "Options" => array("" => "Please Select") + SQLToArray("select ProviderID, DisplayName
			    from Provider
			    order by DisplayName", "ProviderID", "DisplayName"),
			),
			"PageType" => array(
			    "Type" => "integer",
				"Options" => array(
					"" => "Please Select",
					1 => "Rewards Chart",
					2 => "Terms and Conditions",
				),
				"Required" => True
			),
			"PageName" => array(
			    "Type" => "string",
				"Size" => 250,
				"InputAttributes" => "style=\"width: 800px;\"",
				"Required" => True,
			),
			"PageURL" => array(
				"Caption" => "Page URL",
			    "Type" => "string",
				"InputAttributes" => "style=\"width: 800px;\"",
				"Size" => 250,
				"Required" => True,
			),
			"TextToLookFor" => array(
			    "Type" => "string",
				"InputType" => "textarea",
				"InputAttributes" => "style=\"width: 800px; height: 200px;\"",
			),
			"Notes" => array(
			    "Type" => "string",
				"InputType" => "textarea",
				"InputAttributes" => "style=\"width: 800px; height: 100px;\"",
			),
			"Status" => array(
				"Type" => "integer",
				"Options" => $arPageStatus,
			),
			"OldHTML" => array(
				"Caption" => "Old HTML",
			    "Type" => "string",
				"InputType" => "textarea",
				"InputAttributes" => "style=\"width: 800px; height: 500px;\"",
				"HTML" => true,
			),
			"CurHTML" => array(
				"Caption" => "Current HTML",
			    "Type" => "string",
				"InputType" => "textarea",
				"InputAttributes" => "style=\"width: 800px; height: 500px;\"",
				"HTML" => true,
			),
			"StartText" => array(
			    "Type" => "string",
				"InputType" => "textarea",
				"InputAttributes" => "style=\"width: 800px; height:100px;\"",
				"HTML" => true,
			),
			"EndText" => array(
			    "Type" => "string",
				"InputType" => "textarea",
				"InputAttributes" => "style=\"width: 800px; height:100px;\"",
				"HTML" => true,
			),
		);
		$this->ListClass = "TProviderPageList";
	}

	function GetListFields(){
		$arFields = parent::GetListFields();
		unset($arFields["TextToLookFor"]);
		unset($arFields["Notes"]);
		unset($arFields["PageURL"]);
		unset($arFields["OldHTML"]);
		unset($arFields["CurHTML"]);
		unset($arFields["StartText"]);
		unset($arFields["EndText"]);
		return $arFields;
	}

	function GetFormFields(){
		$arFields = parent::GetFormFields();
		unset($arFields['Status']);
		return $arFields;
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		if($form->ID > 0){
			$form->Fields["OldHTML"]["Note"] = "<a href=\"/admin/viewProviderPage.php?ID={$form->ID}&Field=Old\" target=\"_blank\">View</a> | <a href=\"/admin/diffProviderPage.php?ID={$form->ID}\" target=\"_blank\">Diff</a>";
			$form->Fields["CurHTML"]["Note"] = "<a href=\"/admin/viewProviderPage.php?ID={$form->ID}&Field=Cur\" target=\"_blank\">View</a> | <a href=\"/admin/diffProviderPage.php?ID={$form->ID}\" target=\"_blank\">Diff</a>";
		}
		$form->OnSave = array(&$this, "FormSaved", &$form);
	}

	function FormSaved(&$objForm){
		CheckProviderPage($objForm->ID);
	}

}

function CheckProviderPage($nID){
	global $Connection;
	$q = new TQuery("select * from ProviderPage where ProviderPageID = $nID");
	if($q->EOF)
		DieTrace("ProviderPage $nID not found");
	$http = new HttpBrowser("", new CurlDriver());
	$http->GetURL($q->Fields["PageURL"]);
	$arFields = array();
	if(isset($http->Response['headers']['content-type']) && ($http->Response['headers']['content-type'] == 'application/pdf'))
		$http->Response['body'] = "%PDF".base64_encode($http->Response['body']);
	if($q->Fields['OldHTML'] == '')
		$arFields['Status'] = PAGE_STATUS_MATCH;
	else
		if($q->Fields["TextToLookFor"] == "")
			$arFields['Status'] = ComparePageHTML($q->Fields['OldHTML'], $http->Response['body'], $q->Fields['StartText'], $q->Fields['EndText']);
		else
			$arFields['Status'] = PageContainsText($http->Response['body'], $q->Fields['TextToLookFor']);
	$s = $http->Response['body'];
	$arFields['CurHTML'] = "''";
	$Connection->Execute(UpdateSQL("ProviderPage", array("ProviderPageID" => $nID), $arFields));
	while(strlen($s) > 0){
		$bit = substr($s, 0, 4096);
		$s = substr($s, strlen($bit));
		$Connection->Execute("update ProviderPage set CurHTML = concat(CurHTML, '".addslashes($bit)."') where ProviderPageID = $nID");
	}
	if($q->Fields['OldHTML'] == '')
		$Connection->Execute("update ProviderPage set OldHTML = CurHTML where ProviderPageID = $nID");
	return $arFields['Status'];
}

function ComparePageHTML($sOldHTML, $sNewHTML, $sStartText, $sEndText){
	$nResult = ClipPageHTML($sOldHTML, $sStartText, $sEndText);
	if($nResult !== true)
		return $nResult;
	$nResult = ClipPageHTML($sNewHTML, $sStartText, $sEndText);
	if($nResult !== true)
		return $nResult;
	if($sOldHTML == $sNewHTML)
		return PAGE_STATUS_MATCH;
	else
		return PAGE_STATUS_DIFF;
}

// clips page text by start and end signature
// returns true on success, or PAGE_STATUS_MISSING_START, PAGE_STATUS_MISSING_END on error
function ClipPageHTML(&$sHTML, $sStartText, $sEndText){
	if($sStartText != ""){
		$pos = stripos($sHTML, $sStartText);
		if($pos === false)
			return PAGE_STATUS_MISSING_START;
		$sHTML = substr($sHTML, $pos);
	}
	if($sEndText != ""){
		$pos = stripos($sHTML, $sEndText);
		if($pos === false)
			return PAGE_STATUS_MISSING_END;
		$sHTML = substr($sHTML, 0, $pos + strlen($sEndText));
	}
	return true;
}

function PageContainsText($html, $text){
	$html = strip_tags($html);
	$html = CleanXMLValue($html);
	$text = CleanXMLValue($text);
	if(stripos($html, $text) === false)
		return PAGE_STATUS_DIFF;
	else
		return PAGE_STATUS_MATCH;
}

?>

<?

use AwardWallet\MainBundle\Security\StringSanitizer;

class TList extends TBaseList{
	
	var $title = null;

	function __construct($table, $fields, $defaultSort){
		parent::__construct($table, $fields, $defaultSort);
		$this->showTopNav = false;
	}

	// draw list header, called if list is not empty
	function DrawHeader()
	{
		global $lastRowKind;
		if($this->showTopNav)
			$this->drawPageDetails("bottom", True);
		if(isset($this->InplaceForm->Error))
			echo "<div class=formerror>{$this->InplaceForm->Error}<br><br></div>";
		?>
		<table cellspacing="0" cellpadding="0" class="roundedTable" <?/*=$this->tableParams*/?>">
		<?
		if(isset($this->title)) {
		?>
			<tr class="afterTop">
				<td colspan=" <?=( count( $this->Fields ) + 1 + ($this->MultiEdit?1:0) )?>" class="tabHeader">
					<div class="icon"><div class="left"></div></div>
					<div class="caption"><?=$this->title?></div>
				</td>
			 </tr>
			<tr class="head noWrap afterGroup">
		<?
		} else {
		?>
			<tr class="head noWrap after<?=$lastRowKind?>">
		<?
		}
		$lastRowKind = "Head";
		if( !$this->ReadOnly && $this->MultiEdit )
			echo "  <td class='c1head' style='width: 1px'>Select</td>\n";
		$this->DrawFieldHeaders();
		if( !$this->ReadOnly || $this->AlwaysShowEditLinks )
			echo "  <td class=white width=1%></td>\n";
		echo "</tr>\n";
		if( $this->ShowFilters )
			$this->DrawFilters();
		echo "<form method=post name=list_{$this->Table}>";
		if(isset($this->InplaceForm))
			echo "<input type=hidden name=DisableFormScriptChecks value=0>";
		echo "<input type=hidden name=action>\n";
        echo "<input type='hidden' name='FormToken' value='". GetFormToken() ."'>\n";
		if($this->TopButtons){
			echo "<tr><td colspan=".(count($this->Fields) + 2).">";
			$this->DrawButtons();
			echo "</td></tr>";
		}
	}

	// draws table title
	function DrawFieldHeaders(){
		$fieldNo = 0;
		foreach( $this->Fields as $sField => &$arField ){
			if($fieldNo == 0)
				echo "<td class='c1head'><div class='icon'><div class='inner'></div></div><div class='caption'>".$this->FormatCaption( $arField["Caption"], $sField )."</div></td>";
			else 
				echo "<td>".$this->FormatCaption( $arField["Caption"], $sField )."</td>";
			$fieldNo++;
		}
	}

	// formats field header, add sort links
	function FormatCaption( $sCaption, $sField )
	{
		global $QS;
		if($this->Fields[$sField]["Type"] == "customCode")
			return $sCaption;
		if( isset( $this->Fields[$sField] ) && $this->Fields[$sField]["Sort"] )	{
			$arParams = $QS;
			$arParams["Sort1"] = $sField;
			$arParams["Sort2"] = $this->Sort1;
			if( ( $this->Sort1 == $sField ) && ( ArrayVal( $QS, "SortOrder" ) != "Reverse" ) )
				$arParams["SortOrder"] = "Reverse";
			else
				$arParams["SortOrder"] = "Normal";
			$sCaption = "<a href=".((isset($_SERVER['REDIRECT_URL']))?$_SERVER['REDIRECT_URL']:$_SERVER['SCRIPT_NAME'])."?" . ImplodeAssoc( "=", "&", $arParams, True ) . ">$sCaption</a>";
			if( $this->Sort1 == $sField )
				if( ArrayVal( $QS, "SortOrder" ) != "Reverse" )
					$sCaption .= " <span class='sortArrow'>&nbsp;</span>";
				else
					$sCaption .= " <span class='sortArrowDesc'>&nbsp;</span>";
		}
		return $sCaption;
	}

	// draw filter form. override to show custom filters
	// unset shown values from $arHiddens
	function DrawFiltersForm( &$arHiddens )
	{
		global $lastRowKind, $Interface;
		echo "<tr class='after{$lastRowKind} head'>";
		$lastRowKind = "Head";
		if($this->MultiEdit && !$this->ReadOnly)
			echo "<td>&nbsp;</td>";
		$col = 0;
		foreach( $this->Fields as $sFieldName => $arField )
		{
			$arField['Col'] = $col;
			$this->DrawFieldFilter( $sFieldName, $arField );
			unset( $arHiddens[$sFieldName] );
			$col++;
		}
		if(!$this->ReadOnly)
			echo "<td class='manage'>".$Interface->getEditLinks(array("clear" => "<a href='#' onclick=\"FilterForm = document.forms['editor_form']; clearForm(FilterForm); FilterForm.submit(); return false;\">clear filters</a>"))."</td>";
		echo "</tr>";
	}

	// draw one field filter
	function DrawFieldFilter( $sFieldName, &$arField )
	{
		if( isset( $this->FilterForm->Fields[$sFieldName] ) )
		{
			$classes = "";
			$prefix = "";
			$suffix = "";
			if($arField['Col'] == 0){
				$classes .= " c1head";
				$prefix = "<div class='caption'>";
				$suffix = "</div>";
			}
			if( isset( $this->FilterForm->Fields[$sFieldName]["Error"] ) )
				$classes .=" formErrorCell";
			echo "<td class='{$classes}'>{$prefix}".$this->FilterForm->InputHTML( $sFieldName )."&nbsp;<input type=Image name=s1 width=8 height=7 src='/lib/images/button1.gif' style='border: none; margin-bottom: 1px; margin-right: 0px;'>{$suffix}</td>";
		}
		else {
			$classes = "";
			if ($arField['Col'] == 0){
				$classes .= " c1head";
			}
			echo "<td class='{$classes}'>&nbsp;</td>\n";
		}
	}

	// draw one row
	function DrawRow()
	{
		global $lastRowKind;
		$objRS = &$this->Query;
		$classes = "after".$lastRowKind;
		if( ( $objRS->Position % 2 ) == 1 ){
			$classes .= " whiteBg";
			$lastRowKind = "White";
		}
		else{
			$classes .= " grayBg";
			$lastRowKind = "Gray";
		}
		echo "<tr class='{$classes}'>\n";
		if( !$this->ReadOnly && $this->MultiEdit )
			echo "	<td class='c1' nowrap><input type=checkbox name=sel{$objRS->Fields[$this->KeyField]} value={$objRS->Fields[$this->KeyField]}></td>\n";
		$this->DrawFields();
		if( !$this->ReadOnly || $this->AlwaysShowEditLinks )
		{
			echo "	<td class='pad leftDots noWrap manage'>";
			echo $this->GetEditLinks();
			echo "</td>\n";
		}
		echo "</tr>\n";
	}

	// draw fields, in data row
	function DrawFields()
	{
		$arFieldValues = &$this->Query->Fields;
		$fieldNo = 0;
		foreach( $this->Fields as $sFieldName => &$arField ){
			if($this->InplaceEdit){
				$this->DrawInplaceEdit($sFieldName, $arField);
			}
			else{
				if($fieldNo == 0)
					echo "<td class='c1'><div class='icon'><div class='inner'></div></div><div class='caption'>{$arFieldValues[$sFieldName]}</div></td>\n";
				else
					echo "<td class='pad leftDots'>{$arFieldValues[$sFieldName]}</td>\n";
			}
			$fieldNo++;
		}
	}

	//return edit links html
	function GetEditLinks()
	{
		global $Interface;
		$arFields = &$this->Query->Fields;
		$links = array();
		if(!$this->ReadOnly){
			$links[] = "<a href=edit.php?ID={$arFields[$this->KeyField]}{$this->URLParamsString}>Edit</a>";
			if( $this->AllowDeletes && !$this->MultiEdit )
				$links[] = "<input type=hidden name=sel{$arFields[$this->KeyField]} value=\"\">\n<a href='#' onclick=\"if(confirm('Are you sure you want to delete this record?')){ form = document.forms['list_{$this->Table}']; form.sel{$arFields[$this->KeyField]}.value='1'; form.action.value='delete'; form.submit();} return false;\">Delete</a>";
		}
		return $Interface->getEditLinks($links);
	}

	// show footer of non-empty list
	function DrawFooter()
	{
		global $lastRowKind;
		if($this->PageNavigator != ""){
			echo "<tr class='after{$lastRowKind}'><td class='c1 borderTop whiteBg' colspan='{$this->ColCount}'><div class='icon'><div class='inner'></div></div><div class='caption'>{$this->PageNavigator}</div></td></tr>";
			$lastRowKind = "White";
		}
		echo "<tr><td class='c1 lastRow' colspan='{$this->ColCount}'><div class='icon'></div></td></tr></table>\n";
		if( !$this->ReadOnly && $this->ShowEditors )
		{
			echo "<table width=100% border=0 cellspacing=0 cellpadding=0 class=listFooter>
				<tr><td align=right height='35'>";
			echo "<script src=/lib/scripts/listScripts.js></script>\n";
			$this->DrawButtons();
			echo "</table>";
		}
		echo "</form>";
		if(isset($this->InplaceForm))
			echo $this->InplaceForm->CheckScripts();
		//$this->drawPageDetails("top", False);
	}
	
	function DrawButtons($closeTable=true)
	{
		global $Interface;
		$triggers = array();
		echo "<table id=\"listButtons\" cellspacing=0 cellpadding=0 border=0 width='100%'><tr><td style='text-align: left; border: none;'>";
		if( !$this->Query->IsEmpty && $this->MultiEdit ){
#			echo "<input type='Checkbox' onclick=\"javascript:selectAll(this)\">";
			echo "<input type=checkbox value=\"1\" onclick=\"selectCheckBoxes( this.form, 'sel', this.checked )\"> Select All (".$this->RowCount.")";
			echo "</td><td align='right' style='border: none;'>";
			if( $this->InplaceEdit ) {
				echo "<input id=\"saveChangesId\" class='button' type=button value=\"Save changes\" onclick=\"if(CheckForm(this.form)){ this.form.action.value = 'update'; form.submit();}\"> ";
				$triggers[] = array('saveChangesId', 'Save changes');
			}
			if( $this->AllowDeletes ) {
				echo "<input id=\"DeleteId\" class='button' type=button value=\"Delete\" onclick=\"DeleteSelectedFromList( this.form )\"> ";
				$triggers[] = array('DeleteId', 'Delete');
			}
		}
		if( $this->CanAdd && !$this->ReadOnly ) {
			echo $Interface->DrawButton('Add New', 'onclick="location.href = \'edit.php?ID=0'.$this->URLParamsString.'\'"', 80, " onclick='return false;' ");
			//echo "<input id=\"AddNewId\" class='button' type=button value=\"Add New\" onclick=\"location.href = 'edit.php?ID=0{$this->URLParamsString}'\"> ";
			$triggers[] = array('AddNewId', 'Add New');
		}
		if( $this->ShowExport && (isset( $this->Schema ) || isset($this->ExportName) ) ){
			if( $this->Schema->Name == "" )
				DieTrace("Schema name required for export. Did you forget to call TBaseSchema()?");
			echo "<input id=\"ExportId\" class='button' type=button value=\"Export\" onclick=\"location.href = 'export.php?" . StringSanitizer::encodeHtmlEntities($_SERVER['QUERY_STRING']) . "\"> ";
			$triggers[] = array('ExportId', 'Export');
		}
		if( $this->ShowImport && !$this->ReadOnly && isset( $this->Schema ) ) {
			echo "<input id=\"ImportId\" class='button' type=button value=\"Import\" onclick=\"location.href = 'import.php?Schema={$this->Schema->Name}'\"> ";
			$triggers[] = array('ImportId', 'Import');
		}
		if($this->ShowBack && isset($_GET['BackTo'])) {
			echo "<input id=\"GoBackId\" class='button' type=button value=\"Go Back\" onclick=\"location.href = '".urlPathAndQuery($_GET['BackTo'])."'\"> ";
			$triggers[] = array('GoBackId', 'Go Back');
		}
		if($closeTable)
			echo "</td></tr></table>";
			
		if (isset($Interface) && sizeof($triggers) > 0 && !isset($this->isAddedTriggers)) {
			$this->isAddedTriggers = true;
			$trigButtons = array();
			foreach ($triggers as $trigger) {
				$trigButtons[] = '<input class="button" type="button" value="'.$trigger[1].'" onclick="$(\'#'.$trigger[0].'\').trigger(\'click\');" />';
			}
			$trigg = implode("", $trigButtons);
			$Interface->FooterScripts[] = "
				$('#extendFixedMenu').append(".json_encode("<div align=\"right\" style=\"padding: 0 10px 4px 0;\">{$trigg}</div>").");
			";
		}
	}

}

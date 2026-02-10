<?php

class TableSelectFieldManager extends TAbstractFieldManager {
	
	var $TableName = NULL;
	var $SelectTableName = NULL;
	var $KeyField = NULL;
	var $TableFields = NULL;
	var $DataFieldsSql = NULL;
	var $SelectedOptions = array();
    var $secondParameter = NULL;
	
	function CompleteField(){
		parent::CompleteField();
		/*if( !isset( $this->Fields ) )
			DieTrace( "TableSelectFieldManager->CompleteField: Fields not set for field $this->FieldName" );*/
		if( !isset( $this->TableName ) )
			DieTrace( "TableSelectFieldManager->CompleteField: TableName not set for field $this->FieldName" );
		/*$this->Field["InputType"] = "text";
		foreach( $this->Fields as $sOptionField => &$arOptionField )
			$this->Form->CompleteField( $sOptionField, $arOptionField );*/
	    if(!isset($this->KeyField))
		    $this->KeyField = $this->Form->KeyField;
		/*if( !isset( $this->UniqueFields ) )
			$this->UniqueFields = array_keys( $this->Fields );*/
	}
	
	function inputHTML($sFieldName = null, $arField = null){
		global $Interface;
		
		$dataFields = $this->getDataFields();
		if(!isset($this->TableFields)){
			if(empty($dataFields))
				DieTrace("TableSelectFieldManager->inputHTML: TableFields could not be set");
			$this->TableFields = array();
			$this->TableFields = array("checkbox" => "select");
			foreach($dataFields[0] as $key=>$val)
				$this->TableFields[$key] = $key;			
		}
		$html = '
		<table id="accountShareFields" class="awTable" cellspacing="0" cellpadding="0" width="98%">
			<tr class="caption">
		';
		$i = 0;
		foreach($this->TableFields as $key=>$val){
			$tdStyle = '';
			$tdClass = ($i == 0) ? 'bothBorder' : 'rightBorder';
			if(isset($val["InputAttributes"]))
				$tdStyle = $val["InputAttributes"];
			$html .= '
				<td '.$tdStyle.' class="'.$tdClass.'">
					'.$val['name'].'
				</td>
			';				
			$i++;			
		}
		$html .= "</tr>";
		
		$checkedIDs = $this->getCheckedIDs();
		$disabledIDs = $this->getDisabledIDs();

		foreach($dataFields as $fields){
			$nValue = 0;
            $secondParamValue = "";
            if(isset($this->secondParameter))
                $secondParamValue = "_".$fields[$this->secondParameter];
            $currentValue = $this->createArrayValue($fields);
			if(!empty($this->SelectedOptions)){
				if(in_array($currentValue ,$this->SelectedOptions))
					$nValue = 1;
			} else {
				if(in_array($currentValue ,$checkedIDs))
					$nValue = 1;
			}
			$html .= "<tr>";
			foreach($this->TableFields as $key=>$val){
				if($key === 0){
					$html .= "
						<td class=\"bothBorder left\">
							<a name='acc{$fields[$this->SelectTableName.'ID']}' />
							<input ".(in_array($currentValue ,$disabledIDs) ? 'disabled' : '')." type=\"checkbox\" ".($nValue == 1 ? 'checked="checked"':'')." value=\"1\" name=\"".htmlspecialchars("{$this->TableName}_{$fields[$this->SelectTableName.'ID']}{$secondParamValue}")."\">
						</td>
					";
				} else {
					if(isset($val['formatField']))
						$fieldView = $this->formatedField($val['formatField'], $fields, $key);
					else	
						$fieldView = $fields[$key];
						
					$html .= "
						<td class=\"rightBorder left\">
							".$fieldView."
						</td>
					";
				}				
			}
		}		
		$html .= '
		<tr>
			<td colspan="'.count($this->TableFields).'" class="bothBorder left">
				<input type="checkbox" id="chkAll_'.$this->TableName.'" />&nbsp;&nbsp;Select All
			</td>
		</tr>
		';
		$html .= "</table>";
		$Interface->FooterScripts[] = '
		$("#chkAll_'.$this->TableName.'").click(function(){
			if($(this).attr("checked") == "checked"){
				$("input[name*='.$this->TableName.'_]").filter(":enabled").attr("checked", "checked");
			} else {
				$("input[name*='.$this->TableName.'_]").filter(":enabled").removeAttr("checked");
			}
		});
		';
		return $html;
	}
	
	function getCheckedIDs(){
		if($this->Form->ID == 0)
			return array();		
		$sql = "select * from {$this->TableName} where {$this->KeyField} = {$this->Form->ID}";
		$q = new TQuery($sql);
		$fields = array();
        $i = 0;
		while(!$q->EOF){
			$fields[$i][$this->SelectTableName.'ID'] = $q->Fields[$this->SelectTableName.'ID'];
            if(isset($this->secondParameter))
                $fields[$i][$this->secondParameter] = $q->Fields[$this->secondParameter];
			$q->Next();
            $i++;
		}
		return $fields;
	}
	
	function formatedField($format, $fields, $key){
		switch($format){
			case "formatBalance":{
				return formatFullBalance($fields['Balance'], $fields['Code'], $fields['BalanceFormat']);
			} break;
		}
	}
	
	function getDataFields(){
		if(!isset($this->DataFieldsSql))
			DieTrace("TableSelectFieldManager->getDataFields: DataFieldsSql not set");
        if(!$this->DataFieldsSql)
            return array();
		$q = new TQuery($this->DataFieldsSql);
		$dataFields = array();
		while(!$q->EOF){
			$dataFields[] = $q->Fields;
			$q->Next();
		}
		return $dataFields;
	}
	
	function LoadPostData(&$arData){
		parent::LoadPostData($arData);
        $i = 0;
		foreach($arData as $name => $val){
			if( preg_match("/{$this->TableName}_(\d+)_?(\d*)/", $name, $matches) /*strstr($name, $this->TableName."_")*/){
				$this->SelectedOptions[$i][$this->SelectTableName."ID"] = $matches[1];
                if( isset($this->secondParameter) && isset($matches[2]) )
                    $this->SelectedOptions[$i][$this->secondParameter] = $matches[2];
            }
            $i++;
		}
	}
	
	function SetFieldValue( $arValues ){
		
	}
	
	function Save(){
		global $Connection;		
		$dataFields = $this->getDataFields();
		$disabledIDs = $this->getDisabledIDs();
		foreach($dataFields as $field){
            $currentValue = $this->createArrayValue($field);
            $data = array(
                $this->Form->KeyField       => $this->Form->ID,
                $this->SelectTableName."ID" => intval($field[$this->SelectTableName."ID"])
            );
            if(isset($this->secondParameter))
                $data[$this->secondParameter] = empty($field[$this->secondParameter]) ? 'NULL' : intval($field[$this->secondParameter]);
			if (in_array($currentValue, $disabledIDs))
				continue;

			if(in_array($currentValue, $this->SelectedOptions)){
                $where = array();
                foreach($data as $k => $v){
                    if($v != 'NULL')
                        $where[] = $k." = ".$v;
                    else
                        $where[] = $k." IS NULL ";
                }
                $sql = "
                    SELECT COUNT(*) Count
                    FROM {$this->TableName}
                    WHERE ".implode(" AND ",$where);
                $q = new TQuery($sql);
                if($q->Fields['Count'] == 0)
				    $Connection->Execute( InsertSQL( $this->TableName, $data ) );
			} else {
				$Connection->Execute( DeleteSQL( $this->TableName, $data, True ) );
			}
		}
	}

    function createArrayValue($fields){
        $currentValue = array();
        $currentValue[$this->SelectTableName."ID"] = $fields[$this->SelectTableName."ID"];
        if(isset($this->secondParameter))
            $currentValue[$this->secondParameter] = $fields[$this->secondParameter];
        return $currentValue;
    }

    function getImplodeParametersFromSelectedOptions($parameterName){
        $result = array();
        foreach($this->SelectedOptions as $option){
            if(!empty($option[$parameterName]))
                $result[] = $option[$parameterName];
        }
        return $result;
    }

	function getDisabledIDs($requestId = null) {
		return array();
	}

}

?>

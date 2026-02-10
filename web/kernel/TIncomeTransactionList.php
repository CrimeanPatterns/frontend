<?php

require_once( __DIR__ . "/../manager/reports/common.php" );

class TIncomeTransactionList extends TBaseList {

	function ProcessAction($action, $ids){
		global $Connection;
		//parent::ProcessAction($action, $ids);

		$ids = array();
		foreach($_POST as $key=>$name){
			if(substr($key, 0, 3) == 'sel' && $name)
				$ids[] = intval(substr($key, 3));
		}

		if(!empty($ids)){
			if ($action == 'processed') {
				$Connection->Execute("
					UPDATE IncomeTransaction
					SET Processed = IF(Processed = 1, 0, 1)
					WHERE IncomeTransactionID IN (".implode(',', $ids).")
				");
			}
		}
	}

	function FormatFields($output = 'html'){
		parent::FormatFields($output = 'html');
		foreach(array('Revenue', 'Income', 'Fee') as $key){
			$this->Query->Fields[$key] = number_format_localized($this->Query->Fields[$key], 2);
		}
	}

	function GetEditLinks() {
		return parent::GetEditLinks() . " | <a href=\"/manager/list.php?IncomeTransactionID=".$this->Query->Fields['IncomeTransactionID']."&Schema=UsersIncome\">View</a>";
	}

	function DrawButtons($closeTable = true){
		global $Interface;

		$triggerButtons = "<input class='button' type=button value=\"Processed\" onclick=\"\$('#processedId').trigger('click');\">";
		echo "
        <script type='text/javascript'>
        	function ProcessedTrans(form, action){
        		nCount = checkedCount( form, 'sel' );
				if ( nCount > 0 ) {
					form.action.value = action;
					form.submit();
				}
				else
					window.alert('No items selected');
        	}
        </script>
		<input id=\"processedId\" class='button' type=button value=\"Processed\" onclick=\"ProcessedTrans(this.form, 'processed')\">
		";
		$Interface->FooterScripts[] = "
			$('#extendFixedMenu').append(".json_encode("<div align=\"right\" style=\"padding: 0 10px 4px 0;\">".str_replace("\n", "", $triggerButtons)."</div>").");
		";
	}
}

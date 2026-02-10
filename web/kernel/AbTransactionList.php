<?php

class AbTransactionList extends TBaseList
{
    public function GetEditLinks()
    {
        global $Interface;
        $arFields = &$this->Query->Fields;
        $links = [];
        $links[] = "<a href=\"/manager/list.php?TransactionID=" . $arFields['AbTransactionID'] . "&Schema=AbInvoice\">View</a>";

        return $Interface->getEditLinks($links);
    }

    public function ProcessAction($action, $ids)
    {
        global $Connection;

        $ids = [];

        foreach ($_POST as $key => $name) {
            if (substr($key, 0, 3) == 'sel' && $name) {
                $ids[] = intval(substr($key, 3));
            }
        }

        if (!empty($ids)) {
            if ($action == 'processed') {
                $Connection->Execute("
					UPDATE AbTransaction
					SET Processed = IF(Processed = 1, 0, 1)
					WHERE AbTransactionID IN (" . implode(',', $ids) . ")
				");
            }
        }
    }

    public function DrawButtons($closeTable = true)
    {
        global $Interface;
        parent::DrawButtons();

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
    }
}

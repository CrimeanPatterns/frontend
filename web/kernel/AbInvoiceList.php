<?php

class AbInvoiceList extends TBaseList
{
    public function FormatFields($output = 'html')
    {
        parent::FormatFields($output);

        if ($output !== 'html') {
            return;
        }

        // Transaction
        if (isset($this->Query->Fields['TransactionID'])) {
            $this->Query->Fields['TransactionID'] = '<a href="/manager/list.php?AbTransactionID=' . $this->Query->Fields['TransactionID'] . '&Schema=AbTransaction">#' . $this->Query->Fields['TransactionID'] . '</a>';
        } else {
            $this->Query->Fields['TransactionID'] = '-';
        }
    }

    public function GetFieldFilter($sField, $arField)
    {
        switch ($sField) {
            case 'TransactionID':
                if (isset($arField['Value'])) {
                    if ($arField['Value'] == '-') {
                        return ' and i.TransactionID is null';
                    } else {
                        return ' and i.TransactionID = ' . intval($arField['Value']);
                    }
                } else {
                    return '';
                }

                break;

            default:
                return parent::GetFieldFilter($sField, $arField);
        }
    }

    public function GetEditLinks()
    {
        global $Interface;
        $arFields = &$this->Query->Fields;
        $links = [];
        $links[] = "<a target='_blank' href=\"https://business.awardwallet.com/awardBooking/view/{$arFields['AbRequestID']}\">View</a>";

        return $Interface->getEditLinks($links);
    }

    public function ProcessAction($action, $ids)
    {
        global $Connection;

        $idsStatus = $ids = [];

        foreach ($_POST as $key => $name) {
            if (substr($key, 0, 6) == 'onesel' && $name) {
                $idsStatus[] = intval(substr($key, 6));
            }

            if (substr($key, 0, 3) == 'sel' && $name) {
                $ids[] = intval(substr($key, 3));
            }
        }

        if (!empty($ids)) {
            if ($action == 'createTrans') {
                // Create transaction

                $sql = "
                    SELECT 
                        inf.ServiceName 
                    FROM AbInvoice i
                        JOIN AbMessage m ON m.AbMessageID = i.MessageID
                        JOIN AbRequest r ON r.AbRequestID = m.RequestID
                        LEFT JOIN AbBookerInfo inf ON inf.UserID = r.BookerUserID
                    WHERE i.AbInvoiceID = " . current($ids) . "
                ";
                $q = new TQuery($sql);
                $title = '';

                if (!$q->EOF) {
                    $title = $q->Fields['ServiceName'];
                }

                $sql = "
					INSERT INTO AbTransaction (ProcessDate, Processed, Title) VALUES (NOW(), 0, '" . $title . "')
				";
                $Connection->Execute($sql);
                $tid = $Connection->InsertID();
                $sql = "
					UPDATE AbInvoice
						SET TransactionID = $tid
					WHERE AbInvoiceID IN (" . implode(', ', $ids) . ")";
                $Connection->Execute($sql);
            }

            if ($action == 'undoCreateTrans') {
                $sql = "
					UPDATE AbInvoice
						SET TransactionID = NULL
					WHERE AbInvoiceID IN (" . implode(', ', $ids) . ")";
                $Connection->Execute($sql);
            }

            // Delete empty transactions
            $q = new TQuery("
				SELECT t.AbTransactionID, COUNT(i.AbInvoiceID) AS Count
				FROM AbTransaction t
					LEFT OUTER JOIN AbInvoice i ON i.TransactionID = t.AbTransactionID
				GROUP BY t.AbTransactionID
				HAVING Count = 0
			");

            foreach ($q as $row) {
                $Connection->Execute("
					DELETE FROM AbTransaction
					WHERE AbTransactionID = {$row['AbTransactionID']}
				");
            }
        }
    }

    public function DrawButtons($closeTable = true)
    {
        global $Interface;
        parent::DrawButtons(false);

        $upDownButtons = "<a href=\"#contentBody\">Up</a>&nbsp;<a href=\"#createTransId\">Down</a>&nbsp;";
        $triggerButtons = "&nbsp;<input class='button' type=button value=\"Create transaction\" onclick=\"\$('#createTransId').trigger('click');\">&nbsp;<input class='button' type=button value=\"Undo create transaction\" onclick=\"\$('#undoCreateTransId').trigger('click');\">";
        echo "
        <script type='text/javascript'>
        	function createTrans(form, action){
        		nCount = checkedCount( form, 'sel' );
				if ( nCount > 0 ) {
					if( window.confirm( (action === 'undoCreateTrans'?'Would you like to delete ' +nCount+ ' invoices from the transaction ?':'Would you like to combine ' +nCount+ ' invoices into one transaction?') ) ) {
						form.action.value = action;
						form.submit();
					}
				}
				else
					window.alert('No items selected');
        	}
        </script>
		<input id=\"createTransId\" class='button' type=button value=\"Create transaction\" onclick=\"createTrans(this.form, 'createTrans')\">
		<input id=\"undoCreateTransId\" class='button' type=button value=\"Undo create transaction\" onclick=\"createTrans(this.form, 'undoCreateTrans')\">
		</td></tr></table>
		";
        $Interface->FooterScripts[] = "
            var fixed = $('#extendFixedMenu > div #transactionMenu');
            if (fixed.length === 0) {
			    $('#extendFixedMenu div')
			        .prepend('<span id=\'transactionMenu\' style=\'margin-right: 5px;\'></span>')
			        .find('#transactionMenu')
			        .append(" . json_encode(str_replace("\n", "", $upDownButtons)) . ")
			        .append(" . json_encode(str_replace("\n", "", $triggerButtons)) . ");
            }
		";
    }
}

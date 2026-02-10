<?php

require_once __DIR__ . "/../manager/reports/common.php";

class TUsersIncomeList extends TBaseList
{
    public $totalPrice = 0;
    public $totalFee = 0;
    public $totalIncome = 0;

    public $calcTotals = false;

    public function FormatFields($output = "html")
    {
        $paymentType = $this->Query->Fields['PaymentType'];
        parent::FormatFields($output);
        calcProfit($paymentType,
            $this->Query->Fields['Price'],
            $this->Query->Fields['Fee'],
            $this->Query->Fields['Income']);

        if ($this->Query->Fields['OneCardFee'] > 0 && !isset($_GET['calcOneCardFee'])) {
            $this->Query->Fields['Fee'] += $this->Query->Fields['OneCardFee'];
            $this->Query->Fields['Income'] -= $this->Query->Fields['OneCardFee'];
        }

        if ($this->calcTotals) {
            $this->totalPrice += $this->Query->Fields['Price'];
            $this->totalFee += $this->Query->Fields['Fee'];
            $this->totalIncome += $this->Query->Fields['Income'];
        }

        foreach (['Price', 'Income', 'Fee'] as $key) {
            $this->Query->Fields[$key] = number_format_localized($this->Query->Fields[$key], 2);
        }
    }

    public function DrawFooter()
    {
        if ($this->calcTotals) {
            $this->DrawTotals($this->AddFilters($this->SQL));
        }
        parent::DrawFooter();
    }

    public function DrawTotals($sql)
    {
        $class = get_class();
        $totalsList = new $class($this->Table, $this->Fields, $this->DefaultSort);
        $totalsList->calcTotals = $this->calcTotals;
        $totalsList->SQL = $sql;
        $totalsList->UsePages = false;

        $totalsList->OpenQuery();
        $objRS = &$totalsList->Query;

        if (!$objRS->EOF) {
            while (!$objRS->EOF) {
                $totalsList->FormatFields();
                $objRS->Next();
            }
            echo "
				<tr><td colspan=\"" . (count($this->Fields) - 2) . "\">
					<b>TOTALS:</b>
					<td>" . number_format_localized($totalsList->totalPrice, 2) . "</td>
					<td>" . number_format_localized($totalsList->totalFee, 2) . "</td>
					<td>" . number_format_localized($totalsList->totalIncome, 2) . "</td>
					</td>
				</tr>";
        }
    }

    public function DrawButtonsInternal()
    {
        $triggers = parent::DrawButtonsInternal();
        $triggers[] = ['calcTotals', 'Calculate Totals'];

        return $triggers;
    }

    public function DrawButtons($closeTable = true)
    {
        global $Interface;

        $custom = '<input type="submit" value="Calculate Totals" name="calcTotals" id="calcTotals">';

        if (!$this->Query->IsEmpty) {
            echo $custom;
        }

        parent::DrawButtons($closeTable);

        $topText = '';

        $totalStr = '';
        $triggerButtons = "$totalStr<br /><a href=\"#contentBody\">Up</a>&nbsp;<a href=\"#createTransId\">Down</a>&nbsp;<input class='button' type=button value=\"Create transaction\" onclick=\"\$('#createTransId').trigger('click');\"><input class='button' type=button value=\"Undo create transaction\" onclick=\"\$('#undoCreateTransId').trigger('click');\">";
        echo "
		<script type='text/javascript'>
			function createTrans(form, action){
				nCount = checkedCount( form, 'sel' );
				if ( nCount > 0 ) {
					if( window.confirm( (action == 'undoCreateTrans'?'Would you like to delete ' + nCount + ' requests from the transaction ?':'Would you like to combine ' +nCount+ ' requests into one transaction?') ) ) {
						form.action.value = action;
						form.submit();
					}
				}
				else
					window.alert('No items selected');
			}
		</script>
		<input id=\"createTransId\" class='button' type=button value=\"Create transaction\" onclick=\"createTrans(this.form, 'createTrans')\">
		<input id=\"undoCreateTransId\" class='button' type=button value=\"Undo create transaction\" onclick=\"createTrans(this.form, 'undoCreateTrans')\">";

        $Interface->FooterScripts[] = "
			$('#extendFixedMenu div').prepend(" . json_encode(str_replace("\n", "", $topText)) . ");
		";

        $Interface->FooterScripts[] = "
			$('#extendFixedMenu').append('<div align=\"right\" style=\"padding: 0 10px 4px 0;\">" . addslashes(str_replace("\n", "", $triggerButtons)) . "</div>');
		";
    }

    public function GetFieldFilter($sField, $arField)
    {
        switch ($sField) {
            case "IncomeTransactionID":
                if (isset($arField['Value'])) {
                    if ($arField['Value'] == '-') {
                        return " and IncomeTransactionID is null";
                    } else {
                        return " and IncomeTransactionID = " . intval($arField['Value']);
                    }
                } else {
                    return "";
                }

                break;

            default:
                return parent::GetFieldFilter($sField, $arField);
        }
    }

    public function ProcessAction($action, $ids)
    {
        global $Connection;
        // paren	t::ProcessAction($action, $ids);

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
					INSERT INTO IncomeTransaction (Date, Processed) VALUES (NOW(), 0)
				";
                $Connection->Execute($sql);
                $tid = $Connection->InsertID();
                $sql = "
					UPDATE Cart
						SET IncomeTransactionID = $tid
					WHERE CartID IN (" . implode(',', $ids) . ")";
                $Connection->Execute($sql);
            }

            if ($action == 'undoCreateTrans') {
                $sql = "
					UPDATE Cart
						SET IncomeTransactionID = NULL
					WHERE CartID IN (" . implode(',', $ids) . ")";
                $Connection->Execute($sql);
            }

            // Delete empty transactions
            $q = new TQuery("
				SELECT
					it.IncomeTransactionID,
					COUNT(c.CartID) AS Count
				FROM IncomeTransaction it
					LEFT OUTER JOIN Cart c ON c.IncomeTransactionID = it.IncomeTransactionID
				GROUP BY it.IncomeTransactionID
				HAVING Count = 0
			");

            foreach ($q as $row) {
                $Connection->Execute("
					DELETE FROM IncomeTransaction
					WHERE IncomeTransactionID = {$row['IncomeTransactionID']}
				");
            }
        }
    }

    public function GetEditLinks()
    {
        $arFields = &$this->Query->Fields;
        $s = "<a href='/manager/cart/contents.php?CartID={$arFields['CartID']}' target='_blank'>View</a>";

        return $s;
    }

    public function GetExportParams(&$arCols, &$arCaptions)
    {
        parent::GetExportParams($arCols, $arCaptions);

        foreach (['Price', 'Fee', 'Income'] as $fieldName) {
            $arCaptions[$fieldName] = $fieldName;
            $arCols[$fieldName] = ['Caption' => $fieldName];
        }
    }
}

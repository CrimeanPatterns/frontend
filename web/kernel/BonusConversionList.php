<?php

class BonusConversionList extends TBaseList
{
    private $totalCost;
    private $totalReferralsIncome;
    private int $totalTransactions = 0;
    private int $paidTransactions = 0;

    public function __construct($table, $fields, $defaultSort)
    {
        parent::__construct($table, $fields, $defaultSort);

        $this->FilterTable = "bc";
        $this->SQL = "
			SELECT
				bc.BonusConversionID,
				bc.Airline,
				bc.Points,
				bc.Miles,
				bc.CreationDate,
				bc.Processed,
				bc.Cost,
				u.UserID,
				concat( u.FirstName, ' ', u.LastName ) as UserName,
				bc.AccountID,
				a.Login,
				MAX(bcPrev.CreationDate) as PrevRedeem
			FROM
				BonusConversion bc
			LEFT JOIN BonusConversion bcPrev ON
				bcPrev.UserID = bc.UserID AND
				bcPrev.CreationDate < bc.CreationDate
			LEFT JOIN Usr u on bc.UserID = u.UserID
			LEFT JOIN Account a on a.AccountID = bc.AccountID
			WHERE
			1 = 1
			[Filters]
			GROUP BY 
			    bc.BonusConversionID,
				bc.Airline,
				bc.Points,
				bc.Miles,
				bc.CreationDate,
				bc.Processed,
				bc.Cost,
				u.UserID,
				UserName,
				bc.AccountID,
				a.Login
			";

        $this->CanAdd = false;
        $this->InplaceEdit = false;
    }

    public function FormatFields($output = "html")
    {
        parent::FormatFields($output);
        $referralPayments = getSymfonyContainer()->get('aw.referral_income_manager')->getTotalReferralIncomeByUser($this->Query->Fields['UserID'], $this->Query->Fields['PrevRedeem'], $this->Query->Fields['CreationDate']);
        $this->Query->Fields['ReferralsIncome'] = "<a href='/manager/reports/usersIncome.php?CartID=&UserID=&PaymentType=&FirstName=&LastName=&PayDate=&CameFrom=&" .
            "InviterID={$this->Query->Fields['UserID']}&" .
            "fromDate={$this->Query->Fields['PrevRedeem']}&" .
            "toDate={$this->Query->Fields['CreationDate']}&" .
            "Price=&Fee=&Income='>" . $referralPayments . "</a>";
        $this->totalCost = bcadd($this->totalCost, $this->Query->Fields['Cost'], 7);
        $this->totalReferralsIncome = bcadd($this->totalReferralsIncome, $referralPayments, 7);
        $this->totalTransactions++;

        if ('Yes' === $this->Query->Fields['Processed']) {
            $this->paidTransactions++;
        }
    }

    public function DrawButtonsInternal()
    {
        $triggers = parent::DrawButtonsInternal();
        echo "<input id=\"SendEmail\" class='button' type=button value=\"Send Emails\" onclick=\"{ this.form.action.value = 'sendEmail'; form.submit();}\"> ";
        $triggers[] = ['SendEmail', 'Send Email'];

        return $triggers;
    }

    public function ProcessAction($action, $ids)
    {
        switch ($action) {
            case "sendEmail":
                if (!empty($ids)) {
                    $ids = array_map("intval", $ids);

                    if ($ids) {
                        sendBonusConversionMail($ids);
                    }
                }

                break;

            default:
                parent::ProcessAction($action, $ids);
        }
    }

    public function DrawFooter()
    {
        $this->DrawTotals();
        parent::DrawFooter();
    }

    public function DrawTotals()
    {
        $class = get_class();
        /** @var self $totalsList */
        $totalsList = new $class($this->Table, $this->Fields, $this->DefaultSort);
        $totalsList->UsePages = false;
        $totalsList->ShowFilters = true;

        $totalsList->OpenQuery();
        $objRS = &$totalsList->Query;

        if (!$objRS->EOF) {
            while (!$objRS->EOF) {
                $totalsList->FormatFields();
                $objRS->Next();
            }
            echo "<tr><td colspan='7' style='text-align: right;'>";
            echo "<b>TOTALS:</b></td>";
            echo "<td>" . number_format($totalsList->totalCost, 2, ".", ",") . "</td>";
            echo "<td colspan=4>Users transactions: " . $totalsList->totalTransactions . "<br/>";
            echo "Paid Transactions: " . $totalsList->paidTransactions . "<br/>";
            echo "Average user payment: " . ($totalsList->paidTransactions > 0 ? number_format($totalsList->totalCost / $totalsList->paidTransactions, 2, ".", ",") : "n/a") . "<br/>";
            echo "</td>";
            echo "<td>" . number_format($totalsList->totalReferralsIncome, 2, ".", ",") . "</td>";
            echo "</tr>";
        }
    }

    public function GetEditLinks()
    {
        $links = parent::GetEditLinks();
        $links .= " | <a href='list.php?BonusConversionID=&Airline=&Points=&Miles=&CreationDate=&Processed=&Cost=&UserID=" . $this->Query->Fields["UserID"] . "&UserName=&AccountID=&Login=&&Schema=BonusConversion'>Total Cost</a>";

        return $links;
    }
}

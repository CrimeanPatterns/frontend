<?php

class QsTransactionStateAdminList extends TBaseList
{
    private $creditCards = [];

    public function DrawHeader()
    {
        parent::DrawHeader();
        $connection = getSymfonyContainer()->get('doctrine')->getManager()->getConnection();
        $cards = $connection->fetchAll('SELECT CreditCardID, Name FROM CreditCard');
        $this->creditCards = [];
        foreach ($cards as $card) {
            $this->creditCards[$card['CreditCardID']] = $card['Name'];
        }
    }

    public function FormatFields($output = 'html')
    {
        parent::FormatFields($output);
        $this->Query->Fields = $this->formatFieldsRow($this->Query->Fields);
    }

    public function formatFieldsRow($row)
    {
        $row['ClickDate'] = date('j F Y', strtotime($row['ClickDate']));
        $row['UserID'] = '<a href="/manager/list.php?Schema=UserAdmin&UserID=' . $row['UserID'] . '">' . trim($row['FirstName'] . ' ' . $row['LastName']) . ' (' . $row['UserID'] . ') ' . '</a>';

        $row['SubAccountFicoState'] = htmlspecialchars_decode($row['SubAccountFicoState']);
        $row['CreditCardState'] = htmlspecialchars_decode($row['CreditCardState']);

        $subAccounts = json_decode($row['SubAccountFicoState']);
        if (!empty($subAccounts)) {
            $html = '<table class="state-table">';
            $html .= '<tr><th>Code</th><th>Score</th><th>AccountID</th><th>SubAccountID</th><th>Success Check Date</th></tr>';
            foreach ($subAccounts as $subAccount) {
                $html .= '<tr>';
                $html .= '<td>' . $subAccount->code . '</td>';
                $html .= '<td>' . $subAccount->balance . '</td>';
                $html .= '<td>' . $subAccount->accountId . '</td>';
                $html .= '<td>' . $subAccount->subAccountId . '</td>';
                $html .= '<td>' . $subAccount->successCheckDate . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';

            $row['SubAccountFicoState'] = $html;
        }

        $creditCards = json_decode($row['CreditCardState']);
        if (!empty($creditCards)) {
            $html = '<table class="state-table">';
            $html .= '<tr><th>CardID</th><th>Name</th><th>EarliestSeenDate</th><th>LastSeenDate</th></tr>';
            foreach ($creditCards as $creditCard) {
                $html .= '<tr>';
                $html .= '<td>' . $creditCard->creditCardId . '</td>';
                $html .= '<td>' . (array_key_exists($creditCard->creditCardId, $this->creditCards) ? $this->creditCards[$creditCard->creditCardId] : '') . '</td>';
                $html .= '<td>' . $creditCard->earliestSeenDate . '</td>';
                $html .= '<td>' . $creditCard->lastSeenDate . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';

            $row['CreditCardState'] = $html;
        }

        return $row;
    }

    public function DrawFooter()
    {
        parent::DrawFooter();

        echo '<style type="text/css">
            .state-table {
                border-collapse: collapse;
                border-spacing: 0;
            }
            .state-table td, .state-table th {
                padding: .2rem 1rem;
                border: solid 1px #666 !important; 
            }
        </style>';
    }
}

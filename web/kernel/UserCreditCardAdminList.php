<?php

use Doctrine\DBAL\FetchMode;
use Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Connection;

class UserCreditCardAdminList extends TBaseList
{
    public function FormatFields($output = "html")
    {
        parent::FormatFields($output);

        $this->Query->Fields = $this->formatFieldsRow($this->OriginalFields);
    }

    public function formatFieldsRow($row)
    {
        $row['UserID'] = '<a href="/manager/list.php?Schema=UserAdmin&UserID=' . $row['UserID'] . '">' . trim($row['FirstName'] . ' ' . $row['LastName']) . ' (' . $row['UserID'] . ')</a>';
        $row['CreditCardID'] = '<a href="/manager/sonata/credit-card/' . $row['CreditCardID'] . '/edit">' . $row['CardName'] . ' (' . $row['CreditCardID'] . ')</a>';

        $row['IsClosed'] = '1' == $row['IsClosed'] ? 'true' : '';
        $row['DetectedViaBank'] = '1' == $row['DetectedViaBank'] ? 'true' : '';
        $row['DetectedViaCobrand'] = '1' == $row['DetectedViaCobrand'] ? 'true' : '';
        $row['DetectedViaQS'] = '1' == $row['DetectedViaQS'] ? 'true' : '';
        $row['DetectedViaEmail'] = '1' == $row['DetectedViaEmail'] ? 'true' : '';

        return $row;
    }

    public function GetEditLinks()
    {
        $links = '';

        return $links;
    }

    public function DrawFooter($closeTable = true)
    {
        /** @var Connection $connection */
        $connection = getSymfonyContainer()->get('database_connection');
        $banks = $connection->fetchAllAssociative('
            SELECT DISTINCT p.ProviderID, p.DisplayName
            FROM Provider p
            JOIN CreditCard cc ON (cc.ProviderID = p.ProviderID)
            WHERE Kind = ' . PROVIDER_KIND_CREDITCARD . '
            ORDER BY DisplayName ASC
            ');
        $select = '<select name="bank"><option value="">Issuing Bank</option>';

        foreach ($banks as $bank) {
            $select .= '<option value="' . $bank['ProviderID'] . '"' . ((int) $bank['ProviderID'] === (int) ($_GET['bank'] ?? 0) ? ' selected' : '') . '>' . $bank['DisplayName'] . '</option>';
        }
        $select .= '</select>';

        $searchForm = '
        <div id="repotByUser" style="padding: 15px 10px 0;">
            <form method="get">
                <input type="hidden" name="Schema" value="UserCreditCard">
                Report by User <input name="userReport" type="text" value="'
                . (!empty($_GET['userReport']) ? htmlspecialchars($_GET['userReport'], ENT_QUOTES) : '') . '" placeholder="Email / Login / UserID" style="padding: 3px;margin-top:-3px;" required>
                ' . $select . '
                <button class="btn btn-primary" type="submit">Submit</button>
            </form>
        </div>
        ';

        echo '<script>$("#extendFixedMenu ").prepend("' . addslashes(str_replace(["\n", "\r"], ' ', $searchForm)) . '");</script>';

        if (empty($_GET['userReport']) || array_key_exists('UserID', $_GET)) {
            return;
        }

        $style = '
        <style type="text/css">
        .ucc-popup {
            position: fixed;
            width: 80%;
            max-height: 80%;
            overflow: auto;
            top: 100px;
            left: 10%;
            background: #fff;
            z-index: 10;
            padding: .5rem 1rem 2rem;
        }
        .ucc-overlay {
            position: fixed;
            width: 100%;
            height: 100%;
            left:0;
            top:0;
            z-index: 9;
            background: rgba(0,0,0,.5);
        }
        .ucc-popup h2,
        .ucc-popup h3 {
            margin-bottom: 0;
        }
        .ucc-popup table {
            border-collapse: collapse;
            border-spacing: 0;
        }
        .ucc-popup table th {
            background: #ddd;
            padding: 10px;
        }
        .ucc-popup table td {
            padding: 5px 8px; 
            border: solid 1px #999;
        }
        .ucc-popup table tr:hover {
            background: #eee;
        }
        .cc-preview {
            max-height: 45px;
        }
        .hh3 td {border-width: 0 !important;}
        .hh3 h3 {padding-bottom: 10px;}
        </style>
        <div id="uccOverlay" class="ucc-overlay" onclick="$(\'#uccPopup,#uccOverlay\').hide();$(\'body\').css(\'overflow\', \'auto\')"></div>
        ';

        $html = $style . $this->reportByUser($_GET['userReport']);

        echo '<script>
            $("#body").css("overflow", "hidden");
            $(document.body).append("' . addslashes(str_replace(["\n", "\r"], ' ', $html)) . '");
        </script>';
    }

    protected function getRowColor(): string
    {
        $rowColor = parent::getRowColor();

        return $rowColor;
    }

    private function reportByUser(string $query)
    {
        /** @var Connection $connection */
        $connection = getSymfonyContainer()->get('database_connection');

        $query = trim($query);

        if (filter_var($query, FILTER_VALIDATE_EMAIL)) {
            $userWhere = ' Email LIKE ' . $connection->quote('%' . $query . '%');
        } else {
            $userId = filter_var($query, FILTER_SANITIZE_NUMBER_INT);

            if ($userId == $query) {
                $userWhere = ' UserID = ' . (int) $userId;
            } else {
                $userWhere = ' Login LIKE ' . $connection->quote($query);
            }
        }

        $html = [];
        $user = $connection->executeQuery('SELECT UserID, Email, FirstName, LastName FROM Usr WHERE ' . $userWhere . ' LIMIT 1')->fetch(FetchMode::ASSOCIATIVE);

        if (!empty($user)) {
            $html[] = '<h2>Detected Cards</h2>';
            $html[] = 'User: ' . trim($user['FirstName'] . ' ' . $user['LastName']);
            $html[] = 'UserID: ' . $user['UserID'];
            $html[] = 'Email: ' . $user['Email'];

            $tableHead = '
                <table>
                <thead>
                    <tr>
                        <th>Card Name</th>
                        <th>Card Preview</th>
                        <th>Earliest Seen Date</th>
                        <th>Last Seen Date</th>
                        <th>Detected<br>Via Bank</th>
                        <th>Detected<br>Via Cobranded</th>
                        <th>Detected<br>Via QS</th>
                        <th>Detected<br>Via Email</th>
                    </tr>
                </thead>
                <tbody>
            ';
            $bank = empty($_GET['bank']) ? '' : ' AND cc.ProviderID = ' . ((int) $_GET['bank']);
            $currentCards = $connection->fetchAllAssociative('
                SELECT
                        DATE_FORMAT(ucc.EarliestSeenDate, \'%M %e, %Y\') AS _EarliestSeenDate, DATE_FORMAT(ucc.LastSeenDate, \'%M %e, %Y\') AS _LastSeenDate, ucc.DetectedViaBank, ucc.DetectedViaCobrand, ucc.DetectedViaQS, ucc.DetectedViaEmail,
                        cc.CreditCardID, cc.Name, cc.PictureVer, cc.PictureExt
                FROM UserCreditCard ucc
                JOIN CreditCard cc ON (cc.CreditCardID = ucc.CreditCardID ' . $bank . ')
                WHERE
                        ucc.UserID = ' . (int) $user['UserID'] . '
                    AND ucc.IsClosed = 0
                    AND (
                            LastSeenDate > DATE_SUB(LastSeenDate, INTERVAL 2 YEAR)
                        OR  LastSeenDate IS NULL
                        )
            ');

            $html[] = $tableHead;

            $html[] .= empty($currentCards)
                ? '<tr><td colspan="8">Cards Not Found</td></tr>'
                : $this->tableBody($currentCards);

            $html[] = '<tr class="hh3"><td colspan="8"><h3>Past Cards (closed or LastSeen more than two years)</h3></td></tr>';
            $pastCards = $connection->fetchAllAssociative('
                SELECT
                        DATE_FORMAT(ucc.EarliestSeenDate, \'%M %e, %Y\') AS _EarliestSeenDate, DATE_FORMAT(ucc.LastSeenDate, \'%M %e, %Y\') AS _LastSeenDate, ucc.DetectedViaBank, ucc.DetectedViaCobrand, ucc.DetectedViaQS, ucc.DetectedViaEmail,
                        cc.CreditCardID, cc.Name, cc.PictureVer, cc.PictureExt
                FROM UserCreditCard ucc
                JOIN CreditCard cc ON (cc.CreditCardID = ucc.CreditCardID ' . $bank . ')
                WHERE
                        ucc.UserID = ' . (int) $user['UserID'] . '
                    AND (
                           LastSeenDate < DATE_SUB(LastSeenDate, INTERVAL 2 YEAR)
                        OR ucc.IsClosed = 1
                    )
            ');

            $html[] .= empty($pastCards)
                ? '<tr><td colspan="8">Cards Not Found</td></tr>'
                : $this->tableBody($pastCards);
        } else {
            $html[] = '<h2>User not found</h2>';
        }

        $html = '
         </tbody></table>

        <div id="uccPopup" class="ucc-popup">
            <div class="ucc-content">
            ' . implode('<br>', $html) . '
            </div>
        </div>
        ';

        return $html;
    }

    private function tableBody(array $cards)
    {
        $cardRepository = getSymfonyContainer()->get('doctrine.orm.entity_manager')->getRepository(\AwardWallet\MainBundle\Entity\CreditCard::class);

        $table = '';

        foreach ($cards as $card) {
            $objCard = $cardRepository->find($card['CreditCardID']);
            $imgPath = $objCard->getPicturePath();

            $table .= '<tr>';
            $table .= '<td>' . $card['Name'] . ' (<a href="/manager/edit.php?Schema=CreditCard&ID=' . $card['CreditCardID'] . '" target="card">' . $card['CreditCardID'] . '</a>)</td>';
            $table .= '<td>' . (empty($imgPath) ? '' : '<img class="cc-preview" src="' . $imgPath . '" alt="">') . '</td>';
            $table .= '<td>' . $card['_EarliestSeenDate'] . '</td>';
            $table .= '<td>' . $card['_LastSeenDate'] . '</td>';
            $table .= '<td>' . (empty($card['DetectedViaBank']) ? '' : 'true') . '</td>';
            $table .= '<td>' . (empty($card['DetectedViaCobrand']) ? '' : 'true') . '</td>';
            $table .= '<td>' . (empty($card['DetectedViaQS']) ? '' : 'true') . '</td>';
            $table .= '<td>' . (empty($card['DetectedViaEmail']) ? '' : 'true') . '</td>';
            $table .= '</tr>';
        }

        return $table;
    }
}

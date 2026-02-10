<?php

class QsTransactionAdminList extends TBaseList
{
    private $processData = [];
    private $isPreset = false;
    private $isCpaReport = false;
    private ?\Doctrine\DBAL\Connection $conn;

    private $lastApprovalCardClicks = [];

    private $totals = [
        'clicks' => 0,
        'earnings' => 0,
        'applications' => 0,
        'approvals' => 0,
    ];

    public function DrawHeader()
    {
        parent::DrawHeader();
        $this->isPreset = !empty($_GET['preset']) && !in_array($_GET['preset'],
            ['BlogPostID', 'MID', 'CID', 'Source', 'Exit', 'User']
        );

        if ($this->isCpaReport) {
            $this->conn = getSymfonyContainer()->get('doctrine')->getManager()->getConnection();

            $v = \AwardWallet\MainBundle\Entity\QsTransaction::ACTUAL_VERSION;
            $this->lastApprovalCardClicks = $this->conn->fetchAll('
                SELECT DISTINCT t.Card, t.ProcessDate, t.Earnings, t.Approvals
                FROM QsTransaction t
                INNER JOIN (
                    SELECT MAX(j.ProcessDate) as maxProcessDate, Card FROM QsTransaction j WHERE j.Version = ' . $v . ' AND (j.Earnings > 0 OR j.Approvals > 0) GROUP BY j.Card
                ) t2 ON t2.Card = t.Card AND t2.maxProcessDate = t.ProcessDate
                WHERE t.Version = ' . $v . ' AND (t.Earnings > 0 OR t.Approvals > 0)  
            ');
            $this->lastApprovalCardClicks = array_column($this->lastApprovalCardClicks, null, 'Card');
        } elseif ($this->isPreset) {
            $conn = getSymfonyContainer()->get('doctrine')->getManager()->getConnection();
            // $data = $conn->fetchAll(str_replace('ClickDate', 'ProcessDate', $this->Query->SQL));
            $data = $conn->fetchAll($this->Query->SQL);

            foreach ($data as $index => $row) {
                $date = $row['_Date'] ?? $row['ClickDate'] ?? '';
                $md5 = md5($date . '-' . $row['Account'] . '-' . $row['Card']);
                $this->processData[$md5] = $row;
            }
        }
        /*
        if (false !== strpos($this->Query->SQL, 'ProcessDate as _Date')) {
            $conn = getSymfonyContainer()->get('doctrine')->getManager()->getConnection();
            $data = $conn->fetchAll(str_replace('ProcessDate', 'ClickDate', $this->Query->SQL));

            foreach ($data as $key => $row) {
                $md5 = md5($row['_Date'] . '-' . $row['Account'] . '-' . $row['Card']);
                $this->clickData[$md5] = $row;
            }
        }
        */
    }

    public function FormatFields($output = "html")
    {
        if ($this->isCpaReport) {
            $this->Query->Fields = $this->formstFieldsRowCpaReport($this->Query->Fields);
        } else {
            $this->Query->Fields = $this->isPreset
                ? $this->formatFieldsRowQsPreset($this->Query->Fields)
                : $this->formatFieldsRow($this->Query->Fields);
        }
    }

    public function formatFieldsRow($row)
    {
        $this->totals['clicks'] += $row['Clicks'];
        $this->totals['earnings'] += $row['Earnings'];
        $this->totals['applications'] += $row['Applications'];
        $this->totals['approvals'] += $row['Approvals'];

        if (array_key_exists('_Date', $row)) {
            /*
            $md5 = md5($row['_Date'] . '-' . $row['Account'] . '-' . $row['Card']);
            if (array_key_exists($md5, $this->clickData)) {
                $row['Clicks'] = $this->clickData[$md5]['Clicks'];
            } else {
                $row['Clicks'] .= '.';
            }
            */
            if (empty($row['_Date'])) {
                if (!empty($row['ClickDate'])) {
                    $row['_Date'] = date('m/d/Y', strtotime($row['ClickDate']));
                } else {
                    $row['_Date'] = '-//-';
                }
            } else {
                $row['_Date'] = date('m/d/Y', strtotime($row['_Date']));
            }
        }

        if (!empty($row['Card'])) {
            $query = $_GET;
            $query['Card'] = $row['Card'];

            if (!empty($row['Approvals'])) {
                $query['Approvals'] = 1;
            }
            unset($query['preset']);
            $row['Card'] = '<a href="?' . http_build_query($query) . '">' . $row['Card'] . '</a>';
        }

        /*
        if ((float) $row['Earnings'] > 0.00) {
            $row['CPC'] = '$' . number_format($row['Earnings'] / (int) $row['Clicks'], 2);
        } else {
            $row['CPC'] = '$0.00';
        }
        */
        if (array_key_exists('CPC', $row)) {
            $row['CPC'] = '$' . number_format($row['CPC'], 2);
        } else {
            $row['CPC'] = '';
        }

        if (0 === (int) $row['Approvals']) {
            $row['ApprovalRate'] = '0%';
            $row['EarningApproval'] = '0';
        } else {
            $row['EarningApproval'] = '<b title="' . $row['Earnings'] . ' / ' . $row['Approvals'] . '">$' . ((float) number_format($row['Earnings'] / $row['Approvals'],
                2)) . '</b>';

            $approvalRate = 0; // ($row['Approvals'] / $row['Applications'] * 100);
            $row['ApprovalRate'] = ((float) number_format($approvalRate, 2)) . '%';

            if (100 === (int) $approvalRate) {
                $row['ApprovalRate'] = '<b style="background:darkred;color: #fff;">' . $row['ApprovalRate'] . '</b>';
            } elseif ((int) $approvalRate > 100) {
                $row['ApprovalRate'] = '<b style="background:red;color: #fff;">' . $row['ApprovalRate'] . '</b>';
            } else {
                $row['ApprovalRate'] = '<b>' . $row['ApprovalRate'] . '</b>';
            }
        }

        if (!empty($row['CardName'])) {
            $row['CardName'] = '<a href="/manager/list.php?Schema=Qs_Credit_Card&QsCreditCardID=' . $row['QsCreditCardID'] . '">' . $row['CardName'] . '</a>';
        } elseif (isset($row['Card'])) {
            $row['CardName'] = '' . $row['Card'] . '';
        }
        isset($row['Account']) ? $row['Account'] = \AwardWallet\MainBundle\Entity\QsTransaction::ACCOUNTS[$row['Account']] : null;
        $row['Earnings'] = '$' . number_format($row['Earnings'], 2);

        if (!empty($row['BlogPostID'])) {
            $row['BlogPostID'] = '<a href="https://awardwallet.com/blog/?p=' . $row['BlogPostID'] . '" target="blog">' . $row['BlogPostID'] . '</a>';
        }

        if (!empty($row['User'])) {
            $row['User'] = '<a href="/manager/list.php?Schema=UserAdmin&UserID=' . $row['UserID'] . '">' . trim($row['User']) . ' (' . $row['UserID'] . ')</a>';
        } elseif (isset($row['RefCode'])) {
            $row['User'] = 'RefCode: ' . $row['RefCode'];
        }

        if (!empty($row['AccountLevel']) && !empty($row['UserID'])) {
            $level = array_key_exists($row['AccountLevel'],
                $GLOBALS['arAccountLevel']) ? $GLOBALS['arAccountLevel'][$row['AccountLevel']] : '! LEVEL NOT FOUND !';
            $row['AccountLevel'] = '<a href="/manager/list.php?Schema=AdminCart&UserID=' . $row['UserID'] . '" target="cart">' . $level . '</a>';
        }

        if (!empty($row['Referer'])) {
            $link = rtrim(str_replace(['https://', 'http://', 'www.'], '', $row['Referer']), '/');
            $link = strlen($link) > 27 ? substr($link, 0, 27) . '...' : $link;
            $row['Referer'] = '<a href="' . $row['Referer'] . '" target="_blank">' . $link . '</a>';
        }

        $date = [];
        isset($row['YEAR(t.ClickDate)']) ? $date[] = $row['YEAR(t.ClickDate)'] : null;
        isset($row['MONTH(t.ClickDate)']) ? $date[] = $row['MONTH(t.ClickDate)'] : null;
        isset($row['DAY(t.ClickDate)']) ? $date[] = $row['DAY(t.ClickDate)'] : null;
        empty($row['ClickDate']) ? $row['ClickDate'] = implode('-', $date) : null;
        empty($row['ProcessDate']) ? $row['ProcessDate'] = implode('-', $date) : null;

        return $row;
    }

    public function GetEditLinks()
    {
        $links = '';

        return $links;
    }

    public function DrawButtons($closeTable = true)
    {
        $this->isCpaReport = !empty($_GET['preset']) && 'CPA_Report' == $_GET['preset'];

        parent::DrawButtons($closeTable);
        $this->footerScripts = [];

        $fields = [
            'QS',
            'AccountCard',
            'Card',
            'Account',
            'BlogPostID',
            'MID',
            'CID',
            'Source',
            'Exit',
            'User',
            'CPA_Report',
        ];
        $reportSummary = [];
        $presetSet = (isset($_GET['preset']) && in_array($_GET['preset'], $fields)) ? $_GET['preset'] : null;

        foreach ($fields as $field) {
            $reportSummary[] = $presetSet === $field ? '<a href="#' . $field . '" class="active"><b>' . $field . '</b></a>' : '<a href="#' . $field . '">' . $field . '</a>';
        }
        $uid = (int) ($_GET['uid'] ?? '');

        $triggerButtons = '
<div style="float:left;padding:0 0 10px 10px;">
    <form id="qsForm" method="get" action="/manager/list.php" class="qs-form">
        <div class="qs-filter-date" style="padding: 5px 0;">
            <div style="padding-bottom: 10px;width: 950px;">
                Date: from <input type="date" name="dfrom" value="' . ($_GET['dfrom'] ?? '') . '" title="date from"> to <input type="date" name="dto" value="' . ($_GET['dto'] ?? '') . '" title="date to"> or 
                <select>
                    <option value="">Choose Period</option>
                    <option value="' . date('Y-m-d', strtotime('yesterday')) . '=' . date('Y-m-d',
            strtotime('yesterday')) . '">Yesterday</option>
                    <option value="' . date('Y-m-d', strtotime('monday this week')) . '=' . date('Y-m-d',
                strtotime('sunday this week')) . '">Current Week</option>
                    <option value="' . date('Y-m-d', strtotime('monday last week')) . '=' . date('Y-m-d',
                    strtotime('sunday last week')) . '">Last Week</option>
                    <option value="' . date('Y-m-d', strtotime('first day of this month')) . '=' . date('Y-m-d',
                        strtotime('last day of this month')) . '">Current Month</option>
                    <option value="' . date('Y-m-d', strtotime('first day of last month')) . '=' . date('Y-m-d',
                            strtotime('last day of last month')) . '">Last Month</option>
                    <option value="' . date('Y-m-d', strtotime('first day of 2 month ago')) . '=' . date('Y-m-d',
                                strtotime('last day of 2 month ago')) . '">2 Months Ago</option>
                    <option value="' . date('Y-m-d', strtotime('first day of 3 month ago')) . '=' . date('Y-m-d',
                                    strtotime('last day of 3 month ago')) . '">3 Months Ago</option>
                    <option value="' . date('Y-m-d', strtotime('first day of 6 month ago')) . '=' . date('Y-m-d',
                                        strtotime('last day of last month')) . '">Last 6 Months</option>
                    <option value="' . date('Y-01-01') . '=' . date('Y-m-d') . '">Current Year</option>
                </select>
                <a href="#reset-period" onclick="$(\'#qsForm input[type=date]\').val(\'\')" style="float: right">(reset period)</a>
            </div>
            <div class="qs-filter-summary" style="width: 950px;">
                <input type="text" name="uid" value="' . ($uid ?: '') . '" placeholder="UserID" style="width: 50px;margin-right: 10px;">
                Report summary by:  ' . implode('&nbsp;|&nbsp;', $reportSummary) . str_repeat('&nbsp;', 2)
            . ' <button type="submit"> Apply </button> <a href="#reset-type" onclick="$(\'#qsPreset\').val(\'\');$(\'.qs-filter-summary a\').removeClass(\'active\');" style="float: right">(reset type)</a>
                <br><label><input type="checkbox" name="havingClickDate" value="1" style="position: relative;top:2px;"' . (empty($_GET['havingClickDate']) ? '' : ' checked') . '> Show Earnings by Date of Click</label>
            </div>
        </div>
        <input id="qsPreset" type="hidden" name="preset" value="' . $presetSet . '">
        <input type="hidden" name="Schema" value="Qs_Transaction">
        ' . (isset($_GET['PageSize']) ? '<input type="hidden" name="PageSize" value="' . ((int) $_GET['PageSize']) . '">' : '') . '
        ' . (isset($_GET['Sort1']) ? '<input type="hidden" name="Sort1" value="' . htmlspecialchars($_GET['Sort1'],
                ENT_QUOTES) . '">' : '') . '
        ' . (isset($_GET['Sort2']) ? '<input type="hidden" name="Sort2" value="' . htmlspecialchars($_GET['Sort2'],
                    ENT_QUOTES) . '">' : '') . '
        ' . (isset($_GET['SortOrder']) ? '<input type="hidden" name="SortOrder" value="' . htmlspecialchars($_GET['SortOrder'],
                        ENT_QUOTES) . '">' : '') . '
    </form>
</div>';

        $triggerButtons .= '<div style="float: right;margin:0 10px 10px 0;">
        <button id="exportQmpAction" type="button" style="margin-right: 1rem">Export QMP</button>
        <button id="exportAction" type="button" style="margin-right: 1rem">Export</button>
        </div>';

        $this->footerScripts[] = '
            $("#extendFixedMenu ").prepend(`' . addslashes(str_replace("\n", '', $triggerButtons)) . '`);
            $("select", "#qsForm").change(function() {
                var dates = $(this).val().split("=");
                $(\'input[name="dfrom"]\', "#qsForm").val(dates[0]);
                if (undefined !== dates[1])
                    $(\'input[name="dto"]\', "#qsForm").val(dates[1]);
            });
        ';

        if (!empty($_REQUEST['User'])) {
            $this->footerScripts[] = '$("input[name=\'User\']", "#list-table").val("' . $_REQUEST['User'] . '");';
        }

        $isExtendData = !$this->isCpaReport;

        $style = '<style type="text/css">
            .qs-form label {cursor: pointer}
            .qs-filter-date .active {color: #000;text-decoration: none;font-weight:}
            #contentBody {margin-top: 140px !important;}
            #list-table>thead td b {font-size: 14px;}
            ';

        /*
        if ($isExtendData) {
            $style .= '#list-table>thead>tr>td:nth-child(1), #list-table>tbody>tr>td:nth-child(1) {display: none};';
        }
        */

        if (isset($_GET['preset']) && 'QS' === $_GET['preset']) {
            $style .= '
            #list-table thead tr td:nth-child(6),
            #list-table thead tr td:nth-child(7),
            #list-table thead tr td:nth-child(9) {width: 230px;}
            #list-table thead tr td:nth-child(8) {width: 100px;}
            #list-table tbody tr td:nth-child(5) ~ td{text-align: right;}
            ';
        }
        $style .= '</style>';

        $this->footerScripts[] = '
        $(".qs-filter-summary a", "#qsForm").click(function() { $("a.active", $(this).parent()).removeClass(\'active\'); $("#qsPreset").val($(this).attr("href").substr(1)); $(this).addClass("active").parent().find(">a").not($(this)).removeClass("active"); });
        ';
        $this->footerScripts[] = '$("#exportAction").click(function(e) {location.replace(location.href.split("#")[0] + "&export");});';
        $this->footerScripts[] = '$("#exportQmpAction").click(function(e) {location.replace(location.href.split("#")[0] + "&exportQmp");});';

        $this->footerScripts[] = '
            $(document.body).append(`' . str_replace("\n", '', $style) . '`);
        ';

        if (isset($this->SQL)) {
            $sql = str_replace("\n", ' ', $this->SQL);
            preg_match('/select (.*) from/is', $sql, $match);

            if (!empty($match[1])) {
                $sql = str_replace($match[1],
                    ' SUM(t.Clicks) as Clicks, SUM(t.Applications) as Applications, SUM(t.Approvals) as Approvals, SUM(t.Earnings) as Earnings ',
                    $sql);
                preg_match('/group by (.*)/is', $sql, $match);

                if (!empty($match[1])) {
                    $sql = str_ireplace([$match[1], 'group by'], '', $sql);
                }
            }

            if ($isExtendData) {
                $sums = getSymfonyContainer()->get('database_connection')->fetchAssoc($this->AddFilters($sql));

                if ($this->isPreset) {
                    $sumsClicksProcessDate = getSymfonyContainer()->get('database_connection')->fetchAssoc($this->AddFilters(str_replace('ClickDate',
                        'ProcessDate',
                        $sql)));
                    $sumsClicks = getSymfonyContainer()->get('database_connection')->fetchAssoc($this->AddFilters($sql));
                    $sums['Earnings'] = $sumsClicks['Earnings'];
                    $sums['Approvals'] = $sumsClicks['Approvals'];
                    $sums['Applications'] = $sumsClicks['Applications'];
                } else {
                    // $sumsClicks = getSymfonyContainer()->get('database_connection')->fetchAssoc($this->AddFilters(str_replace('ProcessDate', 'ClickDate', $sql)));
                    $sumsClicks = getSymfonyContainer()->get('database_connection')->fetchAssoc($this->AddFilters($sql));
                    $sums['Clicks'] = ($sumsClicks['Clicks'] ?? 0);
                }

                $this->footerScripts[] = '
                    $("#list-table thead td:contains(\'SUM Clicks\'):first").append("<div> <b>total = ' . number_format($sums['Clicks']) . '</b></div>");
                    $("#list-table thead td:contains(\'SUM Applications\'):first").append("<div> <b>total = ' . number_format($sums['Applications']) . '</b></div>");
                    $("#list-table thead td:contains(\'SUM Approvals\'):first").append("<div> <b>total = ' . number_format($sums['Approvals']) . '</b></div>");
                    $("#list-table thead td:contains(\'SUM Earnings\'):first").append("<div> <b>total = $' . number_format((float) $sums['Earnings'],
                    2) . '</b></div>");
                ';

                if (!empty($sumsClicksProcessDate) && !empty($_GET['havingClickDate'])) {
                    $info = ['<tr><th colspan=\"2\">total by ProcessDate</th></tr>'];
                    $info[] = '<tr><td>SUM Clicks:</td><td>' . number_format($sumsClicksProcessDate['Clicks']) . '</td></tr>';
                    $info[] = '<tr><td>SUM Applications:</td><td>' . number_format($sumsClicksProcessDate['Applications']) . '</td></tr>';
                    $info[] = '<tr><td>SUM Approvals:</td><td>' . number_format($sumsClicksProcessDate['Approvals']) . '</td></tr>';
                    $info[] = '<tr><td>SUM Earnings:</td><td>$' . number_format($sumsClicksProcessDate['Earnings'],
                        2) . '</td></tr>';
                    $this->footerScripts[] = '
                        $("#list-table").after("<table class=\"detailsTable\" cellpadding=\"5\" style=\"margin-top: 1rem\">'
                        . implode('', $info)
                        . '</table>");
                    ';
                }
            }
        }
    }

    public function DrawFooter()
    {
        parent::DrawFooter();
        $this->drawFooterScript();
    }

    public function DrawEmptyList()
    {
        parent::DrawEmptyList();
        $this->drawFooterScript();
    }

    private function formatFieldsRowQsPreset($row)
    {
        $date = $row['_Date'] ?? $row['ClickDate'] ?? '';
        $md5 = md5($date . '-' . $row['Account'] . '-' . $row['Card']);

        if (array_key_exists($md5, $this->processData)) {
            $row['Earnings'] = $this->processData[$md5]['Earnings'];
            $row['Approvals'] = $this->processData[$md5]['Approvals'];
        } else {
            $row['Earnings'] =
            $row['Approvals'] = 0;
        }

        return $this->formatFieldsRow($row);
    }

    private function formstFieldsRowCpaReport($row): array
    {
        /*
                $row['LastApproval'] = $row['LastCPA'] = '';

                $card = $row['Card'];

                if (array_key_exists($card, $this->lastApprovalCardClicks)) {
                    $row['LastApproval'] = $this->lastApprovalCardClicks[$card]['ProcessDate'];
                    $row['LastCPA'] = '$' . number_format($this->lastApprovalCardClicks[$card]['Earnings'], 2);

                    if ((int) $this->lastApprovalCardClicks[$card]['Approvals'] > 1) {
                        $row['LastCPA'] = '$'
                            . number_format((float) $this->lastApprovalCardClicks[$card]['Earnings'] / $this->lastApprovalCardClicks[$card]['Approvals'], 2)
                            . ' (' . $this->lastApprovalCardClicks[$card]['Earnings'] . ')';
                    }
                }


                $row['HighCPA'] = '$' . number_format($row['HighCPA'], 2);
        */

        /*
        $row['LastApproval'] = $row['LastCPA'] = $row['HighCPA'] = '';

        $where = ' WHERE (Advertiser '
            . (empty($row['Advertiser'])
                ? 'IS NULL OR Advertiser = \'\''
                : ' LIKE ' . $this->conn->quote($row['Advertiser']))
            . ') ';
        $where .= ' AND Card LIKE ' . $this->conn->quote($row['Card']);

        $lastApproval = $this->conn->fetchAssociative('SELECT Earnings, ProcessDate, Approvals FROM QsTransaction t ' . $where . ' AND (Earnings > 0 OR Approvals > 0) ORDER BY ClickDate DESC LIMIT 1');

        if ($lastApproval) {
            $row['LastApproval'] = $lastApproval['ProcessDate'];
            $row['LastCPA'] = '$' . number_format((float) $lastApproval['Earnings'], 2);

            if ((int) $lastApproval['Approvals'] > 1) {
                $div = (float) $lastApproval['Earnings'] / (int) $lastApproval['Approvals'];
                $row['LastCPA'] = '$'
                    . number_format($div, 2)
                    . ' (' . $lastApproval['Earnings'] . ')';
            }
        }

        $high = $this->conn->fetchAssociative('SELECT Earnings, Approvals FROM QsTransaction t ' . $where . ' AND (Earnings > 0 OR Approvals > 0) ORDER BY Earnings DESC LIMIT 1');

        if ($high) {
            $row['HighCPA'] = '$' . number_format($high['Earnings'], 2);

            if ((int) $high['Approvals'] > 1) {
                $div = (float) $high['Earnings'] / (int) $high['Approvals'];
                $row['LastCPA'] = '$' . number_format($div, 2) . ' (' . $high['Earnings'] . ')';
            }
        }
*/

        if (isset($_GET['test'])) {
            $card = $row['Card'];
            $v = 'Version = ' . \AwardWallet\MainBundle\Entity\QsTransaction::ACTUAL_VERSION;
            $where = "Card LIKE " . $this->conn->quote($card) . " ";

            $lastClick = $this->conn->fetchOne('SELECT MAX(ClickDate) FROM QsTransaction WHERE ' . $v . ' AND ' . $where);
            $lastApproval = $this->conn->fetchAssociative('SELECT ProcessDate as lastProcessDate, Earnings, Approvals FROM QsTransaction WHERE ' . $v . ' AND ' . $where . ' AND (Earnings > 0 OR Approvals > 0) ORDER BY ProcessDate DESC LIMIT 1');
            $highCpa = $this->conn->fetchOne('SELECT MAX(Earnings) as maxEarnings FROM QsTransaction WHERE ' . $v . ' AND ' . $where . ' AND (Earnings > 0 OR Approvals > 0)');

            $row['LastClickDate'] .= '<br>' . $lastClick;
            $row['LastApproval'] .= '<br>' . ($lastApproval['lastProcessDate'] ?? '');
            $row['LastCPA'] .= '<br>' . ($lastApproval['Earnings'] ?? '');
            $row['HighCPA'] .= '<br>' . $highCpa;
        }

        return $row;
    }

    private function drawFooterScript()
    {
        global $Interface;

        if (empty($Interface->isAlreadyFooterScripts) && !empty($this->footerScripts)) {
            $Interface->isAlreadyFooterScripts = true;
            echo '<script>';

            foreach ($this->footerScripts as $script) {
                echo $script;
            }
            echo '</script>';
        }
    }

    private function test()
    {
        $this->conn = getSymfonyContainer()->get('doctrine')->getManager()->getConnection();
        $v = 'Version = ' . \AwardWallet\MainBundle\Entity\QsTransaction::ACTUAL_VERSION;

        $html = '
<style>
.table2, .table2 td {border: 1px solid #333;padding: 2px 5px;}
</style>
<table class="table table2">
<thead>
<tr>
    <th>Card</th>
    <th>Last Click</th>
    <th>Last Approval</th>
    <th>Last CPA</th>
    <th>High CPA</th>
</tr>
</thead>
<tbody>
';
        $cards = $this->conn->fetchFirstColumn('SELECT DISTINCT Card FROM QsTransaction WHERE ' . $v . ' ORDER BY Card ASC');

        foreach ($cards as $card) {
            $where = "Card LIKE " . $this->conn->quote($card) . " ";

            $lastClick = $this->conn->fetchOne('SELECT MAX(ClickDate) FROM QsTransaction WHERE ' . $v . ' AND ' . $where);
            $lastApproval = $this->conn->fetchAssociative('SELECT ProcessDate as lastProcessDate, Earnings, Approvals FROM QsTransaction WHERE ' . $v . ' AND ' . $where . ' AND (Earnings > 0 OR Approvals > 0) ORDER BY ProcessDate DESC LIMIT 1');
            $highCpa = $this->conn->fetchOne('SELECT MAX(Earnings) as maxEarnings FROM QsTransaction WHERE ' . $v . ' AND ' . $where . ' AND (Earnings > 0 OR Approvals > 0)');

            $html .= '<tr>';
            $html .= '<td>' . $card . '</td>';
            $html .= '<td>' . $lastClick . '</td>';
            $html .= '<td>' . ($lastApproval['lastProcessDate'] ?? '') . '</td>';
            $html .= '<td>' . ($lastApproval['Earnings'] ?? '') . '</td>';
            $html .= '<td>' . $highCpa . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        echo $html;

        exit;
    }
}

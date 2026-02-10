<?php

use AwardWallet\MainBundle\Entity\QsTransaction;

if (isset($_GET['export']) || isset($_GET['exportQmp'])) {
    ob_start();
}

class TQs_TransactionSchema extends TBaseSchema
{
    /** @var ?string */
    private $preset;
    private $dateKey = 'ProcessDate';
    private $conn;

    private $presets = [
        'QS' => 'QS',
        'AccountCard' => 'AccountCard',
        'Card' => 'Card',
        'Account' => 'Account',
        'BlogPostID' => 'BlogPostID',
        'MID' => 'MID',
        'CID' => 'CID',
        'Source' => 'Source',
        'Exit' => 'Exit',
        'User' => 'User',
        'CPA_Report' => 'CPA_Report',
    ];

    public function __construct()
    {
        parent::TBaseSchema();
        $this->preset = $_GET['preset'] ?? 'ProcessDate';

        if (!empty($_GET['havingClickDate'])) {
            $this->dateKey = 'ClickDate';
        }
        // if (!empty($_GET['preset']) && (empty($_GET['havingClickDate']) && (!empty($this->preset) || !empty($_GET['dfrom']) || !empty($_GET['dto']))) && 'QS' !== $_GET['preset']) {
        // $this->dateKey = 'ProcessDate';
        // }
        $this->ListClass = QsTransactionAdminList::class;
        $this->TableName = 'QsTransaction';
        $this->KeyField = $this->dateKey;

        $sumFields = [
            'Clicks' => [
                'Caption' => 'SUM Clicks',
                'Type' => 'integer',
            ],
            'Applications' => [
                'Caption' => 'SUM Applications',
                'Type' => 'integer',
            ],
            /*
            'Approvals' => [
                'Caption' => 'SUM Approvals',
                'Type' => 'integer',
            ],
            */
            'Earnings' => [
                'Caption' => 'SUM Earnings',
                'Type' => 'float',
                'Sort' => 'Earnings DESC, Clicks DESC',
            ],
        ];
        $fields = [
            $this->dateKey => [
                'Caption' => $this->dateKey,
                'Type' => 'string',
                'filterWidth' => 60,
                'Sort' => $this->dateKey . ' DESC',
            ],
            'Account' => [
                'Caption' => 'Account',
                'Type' => 'string',
                'Options' => \AwardWallet\MainBundle\Entity\QsTransaction::ACCOUNTS,
            ],
            'Card' => [
                'Caption' => 'Card Name',
                'Type' => 'string',
                'Sort' => 'Card ASC',
            ],
        ] + $sumFields;

        $extend = [
            'Account' => [
                'Caption' => 'Account',
                'Type' => 'string',
                'Options' => \AwardWallet\MainBundle\Entity\QsTransaction::ACCOUNTS,
            ],
            'Source' => [
                'Caption' => 'Source',
                'Type' => 'string',
                'filterWidth' => 50,
            ],
            'BlogPostID' => [
                'Caption' => 'BlogPostID',
                'Type' => 'integer',
            ],
            'MID' => [
                'Caption' => 'MID',
                'Type' => 'string',
                'filterWidth' => 40,
            ],
            'CID' => [
                'Caption' => 'CID',
                'Type' => 'string',
                'filterWidth' => 40,
            ],
            'Exit' => [
                'Caption' => 'Exit',
                'Type' => 'string',
                'filterWidth' => 50,
                'FilterField' => '`Exit`',
            ],
            'User' => [
                'Caption' => 'UserID',
                'Type' => 'integer',
                'FilterField' => 't.UserID',
            ],
            /*
            'Click_ID' => [
                'Caption' => 'Click_ID',
                'Type' => 'integer',
            ],
            */
        ];

        switch ($this->preset) {
            case $this->presets['Card']:
                unset($fields[$this->dateKey], $fields['Account']);

                break;

            case $this->presets['Account']:
            case $this->presets['BlogPostID']:
            case $this->presets['MID']:
            case $this->presets['CID']:
            case $this->presets['Source']:
            case $this->presets['Exit']:
                unset($fields[$this->dateKey], $fields['Card'], $fields['Account']);
                $fields = array_merge([$this->preset => $extend[$this->preset]], $fields);

                break;

            case $this->presets['User']:
                unset($fields[$this->dateKey], $fields['Card']);
                $fields = array_merge([
                    'UserID' => [
                        'Caption' => 'UserID',
                        'Type' => 'integer',
                        'FilterField' => 't.UserID',
                    ],
                    'Login' => [
                        'Caption' => 'Login',
                        'Type' => 'string',
                    ],
                    'FirstName' => [
                        'Caption' => 'First Name',
                        'Type' => 'string',
                    ],
                    'LastName' => [
                        'Caption' => 'Last Name',
                        'Type' => 'string',
                    ],
                    'AccountLevel' => [
                        'Caption' => 'Account Level',
                        'Type' => 'string',
                    ],
                    'Accounts' => [
                        'Caption' => 'Accounts',
                        'Type' => 'integer',
                    ],
                    'Email' => [
                        'Caption' => 'Email',
                        'Type' => 'string',
                    ],
                    'RefCode' => [
                        'Caption' => 'RefCode',
                        'Type' => 'string',
                        'FilterField' => 't.RefCode',
                    ],
                    'Referer' => [
                        'Caption' => 'Referer',
                        'Type' => 'string',
                    ],
                ], $fields);

                break;

            case $this->presets['AccountCard']:
                unset($fields[$this->dateKey]);
                $fields['Account']['Sort'] = 'RawAccount ASC, Card ASC';
                $this->DefaultSort = 'Account';

                break;

            case $this->presets['QS']:
                $fields = array_merge(
                    ['_Date' => $fields[$this->dateKey]],
                    ['Account' => $fields['Account']],
                    ['Card' => $fields['Card']],
                    ['Clicks' => $sumFields['Clicks']],
                    ['Earnings' => $sumFields['Earnings']],
                    ['CPC' => ['Caption' => 'CPC', 'Type' => 'string']],
                    ['Approvals' => ['Caption' => 'Approvals', 'Type' => 'integer']]
                );
                $fields['_Date']['Caption'] = 'Date';
                $fields['Account']['Sort'] = $this->dateKey . ' ASC, Account ASC, Card ASC';
                $this->DefaultSort = 'Account';

                break;

            case $this->presets['CPA_Report']:
                $fields = [
                    'Advertiser' => [],
                    'Card' => [
                        'FilterField' => 't1.Card',
                    ],
                    'LastClickDate' => [
                        'Caption' => 'Last Click',
                    ],
                    'LastApproval' => [
                        // 'Database' => false,
                        'Sort' => 'LastApproval DESC',
                    ],
                    'LastCPA' => [
                        'Caption' => 'LastCPA',
                        // 'Database' => false,
                        // 'FilterField' => 'j5.lastEarnings',
                    ],
                    'HighCPA' => [
                        'Caption' => 'HighCPA',
                        // 'Database' => false,
                    ],
                ];
                $this->DefaultSort = 'LastApproval';

                break;

            default:
                $this->DefaultSort = 'ClickDate';
                unset($extend['Account']);
                $fields = array_merge(
                    ['QsTransactionID' => ['Caption' => 'QsTransactionID', 'Type' => 'integer']],
                    ['ClickDate' => ['Type' => 'string', 'Sort' => 'ClickDate ASC, ProcessDate ASC']],
                    ['ProcessDate' => ['Type' => 'string']],
                    ['Advertiser' => ['Type' => 'text']],
                    ['Account' => $fields['Account']],
                    ['Card' => $fields['Card']],
                    ['Source' => $extend['Source']],
                    ['BlogPostID' => $extend['BlogPostID']],
                    ['MID' => $extend['MID']],
                    ['CID' => $extend['CID']],
                    ['Exit' => $extend['Exit']],
                    ['User' => $extend['User']],
                    ['Clicks' => ['Type' => 'integer']],
                    ['Refcode' => ['Type' => 'string', 'FilterField' => 't.RefCode']],
                    ['Earnings' => ['Type' => 'float']],
                    ['Approvals' => ['Type' => 'integer']],
                    ['Applications' => ['Type' => 'integer']],
                    ['DeviceType' => ['Type' => 'text']]
                    // ['Click_ID' => $extend['Click_ID']]
                );
        }
        /*
        if ('ClickDate' !== $this->preset) {
            $fields = array_merge($fields, [
                'EarningApproval' => [
                    'Caption' => '$/Approvals',
                    'Type' => 'integer',
                ],
                'ApprovalRate' => [
                    'Caption' => 'Approval Rate',
                    'Type' => 'string',
                ],
            ]);
        }
        */

        $this->Fields = $fields;
    }

    public function GetListFields()
    {
        $arFields = $this->Fields;

        return $arFields;
    }

    public function TuneList(&$list)
    {
        /* @var $list TBaseList */
        parent::TuneList($list);
        $list->KeyField = $this->KeyField;
        $list->CanAdd = false;
        $list->AllowDeletes = false;
        $list->ShowExport = false;
        $list->ShowImport = false;
        $list->UsePages = true;
        $list->MultiEdit = false;

        if (empty($list->DefaultSort) || 'ProcessDate' === $list->DefaultSort) {
            $list->DefaultSort = 'Earnings';
        }
        $list->SQL = $this->getSqlBy();

        if (isset($_GET['export'])) {
            $html = ob_get_contents();
            ob_end_clean();
            $this->exportAction($list);
        } elseif (isset($_GET['exportQmp']) && isset($_GET['preset']) && 'CPA_Report' === $_GET['preset']) {
            $this->exrportCpa($list);
        } elseif (isset($_GET['exportQmp'])) {
            $sort = 'Account DESC, ClickDate DESC';
            $list->Fields = [
                'QsTransactionID' => ['Caption' => 'QsTransactionID', 'Type' => 'integer', 'Sort' => $sort],
                'ClickDate' => ['Caption' => 'Date', 'Type' => 'string', 'Sort' => $sort],
                'Account' => [
                    'Caption' => 'Account',
                    'Type' => 'string',
                    'Options' => \AwardWallet\MainBundle\Entity\QsTransaction::ACCOUNTS,
                    'Sort' => $sort,
                ],
                'Card' => ['Caption' => 'Card Name', 'Type' => 'string', 'Sort' => 'Card ASC'],
                'Source' => ['Caption' => 'Source', 'Type' => 'string', 'filterWidth' => 50, 'Sort' => $sort],
                'BlogPostID' => ['Caption' => 'PID', 'Type' => 'integer', 'Sort' => $sort],
                'MID' => ['Caption' => 'MID', 'Type' => 'string', 'filterWidth' => 40, 'Sort' => $sort],
                'CID' => ['Caption' => 'CID', 'Type' => 'string', 'filterWidth' => 40, 'Sort' => $sort],
                'Exit' => [
                    'Caption' => 'Exit',
                    'Type' => 'string',
                    'filterWidth' => 50,
                    'FilterField' => '`Exit`',
                    'Sort' => $sort,
                ],
                'RefCode' => ['Caption' => 'User', 'Type' => 'string', 'FilterField' => 't.RefCode', 'Sort' => $sort],
                'Clicks' => ['Caption' => 'Clicks', 'Type' => 'integer', 'Sort' => $sort],
                'Earnings' => ['Caption' => 'Earnings', 'Type' => 'string', 'Sort' => $sort],
                'Approvals' => ['Caption' => 'Approvals', 'Type' => 'integer', 'Sort' => $sort],
                'RawVar1' => ['Caption' => 'var1', 'Type' => 'string', 'Sort' => $sort],
                'RawAccount' => ['Caption' => 'Account Original', 'Type' => 'string', 'Sort' => $sort],
                'Applications' => ['Caption' => 'Applications', 'Type' => 'integer', 'Sort' => $sort],
            ];
            $list->DefaultSort = 'ClickDate';
            $this->exportAction($list);
        }
    }

    public function TuneForm(TBaseForm $form)
    {
        $form->KeyField = $this->KeyField;
    }

    public function GetFormFields()
    {
        $arFields = $this->Fields;
        unset($arFields[$this->KeyField]);

        return $arFields;
    }

    public function getSqlBy()
    {
        $conn = getSymfonyContainer()->get('database_connection');

        if (!empty($_GET['startDate'])) {
            $_GET['dfrom'] = $_GET['startDate'];
        }

        if (empty($_GET['dfrom']) && empty($_GET['dto'])) {
            $_GET['dfrom'] = date('Y-m-d', strtotime('first day of previous month'));
            $_GET['dto'] = date('Y-m-d', strtotime('last day of previous month'));
        }

        $where = '1';

        if (!empty($_GET['dfrom'])) {
            $where = $this->dateKey . ' >= ' . $conn->quote($_GET['dfrom']);

            if (!empty($_GET['dto'])) {
                $where = $this->dateKey . ' BETWEEN ' . $conn->quote($_GET['dfrom']) . ' AND ' . $conn->quote($_GET['dto']);
            }
        } elseif (!empty($_GET['dto'])) {
            $where = $this->dateKey . ' <= ' . $conn->quote($_GET['dto']);
        }

        if ('ProcessDate' === $this->dateKey) {
            $where = '(' . $where . ' OR (' . str_replace('ProcessDate',
                'ClickDate',
                $where) . ' AND ProcessDate IS NULL))';
        }
        // $where .= ' AND (' . $this->dateKey . ' IS NOT NULL OR Earnings > 0) ';

        if (!empty($_GET['CardName'])) {
            $where .= ' AND Card LIKE ' . $conn->quote(urldecode($_GET['CardName']));
        }

        if (!empty($_GET['uid'])) {
            $where .= ' AND t.UserID = ' . ((int) $_GET['uid']);
        }

        $sumFields = ', SUM(t.Clicks) as Clicks, SUM(t.Applications) as Applications, SUM(t.Approvals) as Approvals, SUM(t.Earnings) as Earnings ';
        $andActualVersion = ' AND Version = ' . QsTransaction::ACTUAL_VERSION;

        switch ($this->preset) {
            case $this->presets['Card']:
            case $this->presets['Account']:
            case $this->presets['BlogPostID']:
            case $this->presets['MID']:
            case $this->presets['CID']:
            case $this->presets['Source']:
            case $this->presets['Exit']:
                $field = $this->preset;

                if ('Card' === $this->preset) {
                    $xSelect = ', NULL AS Account';
                } elseif ('Account' === $this->preset) {
                    $xSelect = ', NULL AS Card';
                } else {// if (in_array($this->preset, [$this->presets['BlogPostID'], $this->presets['MID'], $this->presets['CID'], $this->presets['Source'], $this->presets['Exit']])) {
                    $xSelect = ', NULL AS Card, NULL AS Account';
                }

                return '
                    SELECT
                            ' . $conn->quoteIdentifier($field) . $sumFields . ',
                            NULL AS _Date
                            ' . $xSelect . '
                    FROM ' . $this->TableName . ' t
                    WHERE ( ' . $where . ' [Filters] ) ' . $andActualVersion . '
                    GROUP BY ' . $conn->quoteIdentifier($field) . '
                    -- ';

            case $this->presets['AccountCard']:
                return '
                    SELECT
                            Account, Card ' . $sumFields . ',
                            NULL AS _Date
                    FROM ' . $this->TableName . ' t
                    WHERE ( ' . $where . ' [Filters] ) ' . $andActualVersion . '
                    GROUP BY RawAccount, Account, Card
                    -- ';

                break;

            case $this->presets['QS']:
                return '
                    SELECT
                        ' . $this->dateKey . ' as _Date, ClickDate, Account, Card, CPC, Approvals ' . $sumFields . '
                    FROM ' . $this->TableName . ' t
                    WHERE ( ' . $where . ' [Filters] ) ' . $andActualVersion . '
                    GROUP BY ' . $this->dateKey . ', ClickDate, Account, Card, CPC, Approvals
                    -- ';

                break;

            case $this->presets['User']:
                return '
                    SELECT
                            t.RefCode, t.UserID, t.Account ' . $sumFields . ',
                            u.Login, u.FirstName, u.LastName, u.Email, u.AccountLevel, u.Accounts, u.Referer,
                            NULL AS _Date,
                            NULL AS Card
                    FROM ' . $this->TableName . ' t
                    LEFT OUTER JOIN Usr u ON (t.UserID = u.UserID)
                    WHERE ( ' . $where . ' [Filters] ) ' . $andActualVersion . '
                    GROUP BY t.RefCode, t.UserID, t.Account, u.FirstName, u.LastName, u.Login
                    -- ';

            case $this->presets['CPA_Report']:
                $v = 'Version = ' . QsTransaction::ACTUAL_VERSION;

                return '
                    SELECT
                        DISTINCT t1.Card, t1.Advertiser,
                        j2.LastClickDate,
                        MAX(j3.maxEarnings) as HighCPA,
                        j5.lastProcessDate as LastApproval, j5.lastEarnings as LastCPA, j5.lastApprovals
                    FROM QsTransaction t1
                    LEFT JOIN (SELECT t2.Card, MAX(t2.ClickDate) as LastClickDate FROM QsTransaction t2 WHERE ' . $v . ' GROUP BY Card) j2 ON j2.Card = t1.Card
                    LEFT JOIN (SELECT t3.Card, MAX(t3.Earnings) as maxEarnings FROM QsTransaction t3 WHERE ' . $v . ' GROUP BY t3.Card, t3.Earnings) j3 ON j3.Card = t1.Card
                    LEFT JOIN (
                        SELECT DISTINCT t22.Card, t22.ProcessDate as lastProcessDate, t22.Earnings as lastEarnings, t22.Approvals as lastApprovals
                        FROM QsTransaction t22
                        INNER JOIN (
                            SELECT MAX(j.ProcessDate) as maxProcessDate, Card FROM QsTransaction j WHERE j.' . $v . ' AND (j.Earnings > 0 OR j.Approvals > 0) GROUP BY j.Card
                        ) t2 ON t2.Card = t22.Card AND t2.maxProcessDate = t22.ProcessDate
                        WHERE t22.' . $v . ' AND (t22.Earnings > 0 OR t22.Approvals > 0)
                    ) j5 ON j5.Card = t1.Card
                    WHERE (' . $where . ' AND t1.' . $v . ' [Filters])
                    GROUP BY t1.Advertiser, t1.Card, j2.LastClickDate, j5.lastEarnings, j5.lastApprovals
                ';

                return '
                    SELECT DISTINCT t1.Card, t1.Advertiser,  j2.LastClickDate, MAX(j3.maxEarnings) as HighCPA
                    FROM QsTransaction t1
                    LEFT JOIN (SELECT t2.Card, MAX(t2.ClickDate) as LastClickDate FROM QsTransaction t2 WHERE 1 ' . $andActualVersion . ' GROUP BY Card) j2 ON j2.Card = t1.Card
                    LEFT JOIN (SELECT t3.Card, MAX(t3.Earnings) as maxEarnings FROM QsTransaction t3 WHERE 1 ' . $andActualVersion . ' GROUP BY t3.Card, t3.Earnings) j3 ON j3.Card = t1.Card
                    WHERE ( ' . $where . ' [Filters] ) ' . $andActualVersion . '
                    GROUP BY t1.Advertiser, t1.Card, j2.LastClickDate
                    -- ';

                return '
                    SELECT
                        Advertiser, Card, MAX(ClickDate) AS LastClickDate
                    FROM QsTransaction t
                    WHERE ( ' . $where . ' [Filters] ) ' . $andActualVersion . '
                    GROUP BY Advertiser, Card
                    ORDER BY LastClickDate DESC
                    -- ';
        }

        return '
            SELECT
                t.QsTransactionID, t.ClickDate, t.ProcessDate, t.Card, t.Account, t.BlogPostID, t.MID, t.CID, t.`Source`, t.`Exit`, t.Clicks, t.Applications, t.Approvals, t.Earnings, t.Click_ID, t.RawVar1, t.RawAccount, t.Refcode,
                t.RefCode, t.UserID, CONCAT(u.FirstName, \' \', u.LastName) as `User`, t.DeviceType, t.Advertiser
            FROM ' . $this->TableName . ' t
            LEFT OUTER JOIN Usr u ON (t.UserID = u.UserID)
            WHERE ( ' . $where . ' [Filters]  ) ' . $andActualVersion;
        /*
                return '
                    SELECT
                        ' . $this->dateKey . ', t.ProcessDate, Card ' . $sumFields . ', t.Account, t.BlogPostID, MID, CID, `Source`, `Exit`, Click_ID,
                        t.RefCode, t.UserID, CONCAT(u.FirstName, \' \', u.LastName) as `User`
                    FROM ' . $this->TableName . ' t
                    LEFT OUTER JOIN Usr u ON (t.UserID = u.UserID)
                    WHERE ' . $where . ' [Filters]
                    GROUP BY Account, ' . $this->dateKey . ', t.ProcessDate, Card, Approvals, BlogPostID, MID, CID, `Source`, `Exit`, Applications, Click_ID, t.RefCode, t.UserID, u.FirstName, u.LastName';
        */
    }

    public function exportAction($objList)
    {
        ini_set('memory_limit', '256M');

        $conn = getSymfonyContainer()->get('database_connection');
        $objList->CreateFilterForm();
        $data = $conn->fetchAll($objList->AddFilters($objList->SQL) . $objList->GetOrderBy());
        $result = [];

        $sql = str_replace("\n", ' ', $objList->SQL);
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
        $sums = $conn->fetchAssoc($objList->AddFilters($sql));

        if (isset($_GET['preset']) && 'QS' === $_GET['preset']) {
            $sumsClicks = $conn->fetchAssoc($objList->AddFilters(str_replace('ClickDate', 'ProcessDate', $sql)));
            $sums['Earnings'] = $sumsClicks['Earnings'];
            $sums['Approvals'] = $sumsClicks['Approvals'];
            $sums['Applications'] = $sumsClicks['Applications'];
        } else {
            $sumsClicks = $conn->fetchAssoc($objList->AddFilters(str_replace('ProcessDate', 'ClickDate', $sql)));
            $sums['Clicks'] = $sumsClicks['Clicks'];
        }

        foreach ($data as $row) {
            $row = $objList->formatFieldsRow($row);
            $line = [];

            foreach ($objList->Fields as $key => $item) {
                if ('ClickDate' === $key && empty($_GET['preset'])) {
                    $line['ClickDate'] = $row['ClickDate'];
                    $line['ProcessDate'] = $row['ProcessDate'];

                    continue;
                }

                $line[$item['Caption']] = $row[$key] ?? null;
            }
            $result[] = $line;
        }
        $heads = empty($result) ? [] : array_keys(current($result));

        if (!isset($_GET['exportQmp'])) {
            foreach ($heads as $indx => $head) {
                $key = str_replace('SUM ', '', $head);

                if (array_key_exists($key, $sums) && !empty($sums[$key])) {
                    if (false !== strpos($key, 'Earnings')) {
                        $value = '$' . number_format($sums[$key], 2);
                    } else {
                        $value = number_format($sums[$key], 0);
                    }

                    $heads[$indx] .= ' (total ' . $value . ')';
                }
            }
        }

        $hideId = (isset($result[0]) && array_key_exists('ID', $result[0]) && empty($result[0]['ID']));
        $fh = fopen('php://output', 'a');

        if ($hideId) {
            unset($result[0]['ID']);
        }

        $fileName = isset($_GET['exportQmp']) ? 'QsTransactionQmp' : 'QsTransaction';

        header("Content-type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename={$fileName}.csv");
        header('Cache-Control: max-age=0');

        fputcsv($fh, $heads);

        $counter = 0;

        foreach ($result as $item) {
            if ($hideId) {
                unset($item['ID']);
            }

            if (1000 === ++$counter) {
                ob_flush();
                flush();
                $counter = 0;
            }

            foreach ($item as &$value) {
                $value = strip_tags($value);
            }
            fputcsv($fh, $item);
        }

        ob_flush();
        flush();

        exit;
    }

    public function exrportCpa($list)
    {
        ini_set('memory_limit', '256M');
        $this->conn = getSymfonyContainer()->get('database_connection');

        $sql = $this->getSqlBy();
        $sql = str_replace('[Filters]', '', $sql);

        $data = $this->conn->fetchAllAssociative($sql);

        foreach ($data as &$row) {
            $row['LastApproval'] = $row['LastCPA'] = $row['HighCPA'] = '';

            $where = ' WHERE (Advertiser ' . (empty($row['Advertiser']) ? 'IS NULL OR Advertiser = \'\'' : ' LIKE ' . $this->conn->quote($row['Advertiser'])) . ') ';
            $where .= ' AND Card LIKE ' . $this->conn->quote($row['Card']);

            $lastApproval = $this->conn->fetchAssociative('SELECT Earnings, ProcessDate, Approvals FROM QsTransaction t ' . $where . ' AND (Earnings > 0 OR Approvals > 0) ORDER BY ClickDate DESC LIMIT 1');

            if ($lastApproval) {
                $row['LastApproval'] = $lastApproval['ProcessDate'];
                $row['LastCPA'] = '$' . number_format($lastApproval['Earnings'], 2);

                if ((int) $lastApproval['Approvals'] > 1) {
                    $div = (float) $lastApproval['Earnings'] / (int) $lastApproval['Approvals'];
                    $row['LastCPA'] = '$' . number_format($div, 2) . '(' . $lastApproval['Earnings'] . ')';
                }
            }

            $high = $this->conn->fetchAssociative('SELECT Earnings, Approvals FROM QsTransaction t ' . $where . ' AND (Earnings > 0 OR Approvals > 0) ORDER BY Earnings DESC LIMIT 1');

            if ($high) {
                $row['HighCPA'] = '$' . number_format($high['Earnings'], 2);

                if ((int) $high['Approvals'] > 1) {
                    $div = (float) $high['Earnings'] / (int) $high['Approvals'];
                    $row['LastCPA'] = '$' . number_format($div, 2) . '(' . $high['Earnings'] . ')';
                }
            }
        }

        $fh = fopen('php://output', 'a');
        $fileName = 'QsTransaction_CPAReport';

        header("Content-type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename={$fileName}.csv");
        header('Cache-Control: max-age=0');

        $heads = ['Advertiser', 'Card', 'LastClick', 'LastApproval', 'LastCPA', 'HighCPA'];
        fputcsv($fh, $heads);

        $counter = 0;

        foreach ($data as $item) {
            if (100 === ++$counter) {
                ob_flush();
                flush();
                $counter = 0;
            }

            foreach ($item as &$value) {
                $value = strip_tags($value);
            }
            fputcsv($fh, $item);
        }

        ob_flush();
        flush();

        exit;
    }
}

<?php

namespace AwardWallet\Manager\Schema;

class QsUserCards extends \TBaseSchema
{
    public function __construct()
    {
        parent::__construct();
        $this->TableName = 'UserCreditCard';
        $this->ListClass = QsUserCardsList::class;

        $this->Fields = [
            'UserID' => [
                'Type' => 'integer',
                'Required' => false,
                'Sort' => 'u.UserID DESC',
                'FilterField' => 'u.UserID',
            ],
            // 'QsTransactionID' => [
            //    'Type' => 'integer',
            // ],
            'FullName' => [
                'Type' => 'string',
                'Required' => false,
                'Sort' => 'u.FirstName',
                'FilterField' => "CONCAT(u.FirstName,' ', u.LastName)",
            ],
            'Email' => [
                'Type' => 'string',
            ],
            'RawVars' => [
                'Type' => 'string',
                'Sort' => 'qt.RawVar1',
                'FilterField' => 'qt.RawVar1',
            ],
            'CreditCardID' => [
                'Type' => 'integer',
                'Sort' => 'cc.CreditCardID',
                'FilterField' => 'cc.CreditCardID',
            ],
            'CardName' => [
                'Type' => 'string',
                'Sort' => 'qt.Card',
                'FilterField' => 'qt.Card',
            ],
            'ClicksDate' => [
                'Type' => 'string',
            ],
            'EarliestSeenDate' => [
                'Type' => 'date',
            ],
            'DetectedViaBank' => [
                'Type' => 'boolean',
                'Caption' => 'Bank',
            ],
            'DetectedViaCobrand' => [
                'Type' => 'boolean',
                'Caption' => 'Cobrand',
            ],
        ];
    }

    public function TuneList(&$list): void
    {
        parent::TuneList($list);
        $list->SQL = $this->getSql();

        $list->MultiEdit =
        $list->InplaceEdit =
        $list->CanAdd =
        $list->ShowExport =
        $list->ShowImport = false;

        $list->DefaultSort = 'UserID';
    }

    public function getSql(): string
    {
        $where = self::getWhere();
        $group = [];

        if (!empty($_GET['Sort1']) && 'RawVars' === $_GET['Sort1']
            || !empty($_GET['Sort2']) && 'RawVars' === $_GET['Sort2']) {
            $group[] = 'qt.RawVar1';
        }
        $group = empty($group) ? '' : ', ' . implode(',', $group);

        return '
            SELECT
                ucc.UserCreditCardID, ucc.UserID, ucc.EarliestSeenDate, ucc.DetectedViaBank, ucc.DetectedViaCobrand,
                qt.Card AS qtCard, MIN(qt.ClickDate) AS startDate,
                    GROUP_CONCAT(DISTINCT RawVar1 SEPARATOR\'||\') AS _RawVars,
                    GROUP_CONCAT(DISTINCT ClickDate SEPARATOR\'||\') AS _ClicksDate,
                u.Email, u.FirstName, u.LastName,
                cc.CreditCardID, cc.CardFullName, cc.Name AS CardName
            FROM UserCreditCard ucc
            JOIN QsTransaction qt ON (qt.UserID = ucc.UserID)
            JOIN Usr u ON (u.UserID = ucc.UserID)
            JOIN CreditCard cc ON (cc.CreditCardID = ucc.CreditCardID AND (cc.QsCreditCardID = qt.QsCreditCardID )) -- OR qt.QsCreditCardID IS NULL
            WHERE
                ' . $where . '
                AND (qt.Approvals IS NULL OR qt.Approvals < 1)
                AND (ucc.EarliestSeenDate >= qt.ClickDate AND qt.ClickDate >= SUBDATE(ucc.EarliestSeenDate, INTERVAL 3 MONTH))
                AND (ucc.DetectedViaBank = 1 OR ucc.DetectedViaCobrand = 1)
                [Filters]
            GROUP BY
                ucc.UserID, ucc.UserCreditCardID, qtCard
                ' . $group . '
        ';
    }

    public static function getWhere(): string
    {
        $conn = getSymfonyContainer()->get('database_connection');

        $where = [];

        if (!empty($_GET['dfrom'])) {
            $where[] = 'CreationDateTime >= ' . $conn->quote($_GET['dfrom'] . ' 00:00:00');

            if (!empty($_GET['dto'])) {
                $where[] = 'CreationDateTime BETWEEN ' . $conn->quote($_GET['dfrom'] . ' 00:00') . ' AND ' . $conn->quote($_GET['dto'] . ' 23:59:59');
            }
        } elseif (!empty($_GET['dto'])) {
            $where[] = 'CreationDateTime <= ' . $conn->quote($_GET['dto'] . ' 23:59:59');
        }

        return empty($where) ? '1' : implode(' AND ', $where);
    }
}

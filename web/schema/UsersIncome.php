<?php

use AwardWallet\MainBundle\Globals\AwReferralIncomeManager;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class TUsersIncomeSchema extends TBaseSchema
{
    public function __construct()
    {
        parent::TBaseSchema();
        global $arPaymentTypeName;
        $this->TableName = "Cart";
        $this->ListClass = "TUsersIncomeList";
        $this->DefaultSort = 'PayDate';
        $this->Fields = [
            'CartID' => [
                'Type' => 'integer',
                'Caption' => 'Cart ID',
                'filterWidth' => 50,
                'FilterField' => 'c.CartID',
            ],
            'UserID' => [
                'Type' => 'integer',
                'Caption' => 'User ID',
                'filterWidth' => 50,
                'FilterField' => 'c.UserID',
            ],
            'PaymentType' => [
                'Type' => 'integer',
                'Caption' => 'Payment type',
                'FilterField' => 'c.PaymentType',
                'Options' => $arPaymentTypeName,
            ],
            'FirstName' => [
                'Type' => 'string',
                'Caption' => 'First Name',
                'filterWidth' => 50,
                'FilterField' => 'c.FirstName',
            ],
            'LastName' => [
                'Type' => 'string',
                'Caption' => 'Last Name',
                'filterWidth' => 50,
                'FilterField' => 'c.LastName',
            ],
            'PayDate' => [
                'Type' => 'date',
                'IncludeTime' => true,
                'Caption' => 'Pay Date',
                'FilterField' => 'c.PayDate',
            ],
            'CameFrom' => [
                'Type' => 'integer',
                'Caption' => 'Referrer',
                'filterWidth' => 300,
                'FilterField' => 'COALESCE(c.CameFrom)',
                'Options' => SQLToArray("
                    SELECT
                        s.SiteAdID, CONCAT(s.Description, ' [', COUNT(u.UserID), ' users registered]') AS Description,
                        COUNT(u.UserID) AS _usersCount
                        FROM SiteAd s
                    LEFT JOIN Usr u ON (u.CameFrom = s.SiteAdID)
                    GROUP BY s.SiteAdID, s.Description
                    ORDER BY s.Description ASC", 'SiteAdID', 'Description'),
            ],
            'InviterID' => [
                'Type' => 'integer',
                'Caption' => 'Inviter ID',
                'filterWidth' => 50,
                'FilterField' => 'c.InviterID',
            ],
            'Price' => [
                'Type' => 'float',
                'Caption' => 'Price',
                'filterWidth' => 50,
                'FilterField' => 'Price',
                'Database' => false,
            ],
            'Fee' => [
                'Type' => 'float',
                'Caption' => 'Fee',
                'Database' => false,
            ],
            'Income' => [
                'Type' => 'float',
                'Caption' => 'Income',
                'Database' => false,
            ],
        ];
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);

        global $Connection;
        $fromDate = !empty($_GET['fromDate']) ? " AND %s > " . $Connection->DateTimeToSQL(strtotime($_GET['fromDate'])) : '';
        $toDate = !empty($_GET['toDate']) ? " AND %s < " . $Connection->DateTimeToSQL(strtotime($_GET['toDate'])) : '';

        $excludedTypesSql = it(AwReferralIncomeManager::getExcludedCartItemTypes())->joinToString(', ');
        $list->SQL = "
        SELECT *
        FROM (
                select
                    c.CartID,
                    c.UserID,
                    c.PayDate,
                    c.PaymentType,
                    u.FirstName,
                    u.LastName,
                    COALESCE(c.CameFrom, u.CameFrom) as CameFrom,
                    i.InviterID,
                    c.IncomeTransactionID,
                    sum(ci.Price * ci.Cnt * ((100-ci.Discount)/100)) as Price,
                    sum(case when ci.TypeID = " . CART_ITEM_ONE_CARD . " then 1.5 * ci.Cnt else 0 end) as OneCardFee
                from
                    Cart c
                    join CartItem ci on c.CartID = ci.CartID
                    left join CartItem cib on 
                        ci.CartID = cib.CartID and
                        cib.TypeID in ({$excludedTypesSql})
                    join Usr u on c.UserID = u.UserID
                    left join Invites i on c.UserID = i.InviteeID
                where
                        ci.TypeID not in ({$excludedTypesSql})
                    and c.PaymentType <> " . PAYMENTTYPE_BUSINESS_BALANCE . "
                    and c.PaymentType IS NOT NULL
                    and ci.Cnt > 0 and ci.Discount < 100 and (ci.Price <> 0 or ci.TypeID = " . CART_ITEM_ONE_CARD . ")
                    and c.UserID is not null
                    and c.PayDate is not null
                    and ci.ScheduledDate is null
                    and cib.CartItemID is null
                    " . sprintf($fromDate, 'c.PayDate') . "
                    " . sprintf($toDate, 'c.PayDate') . "
                group by
                    c.CartID,
                    c.UserID,
                    c.PayDate,
                    c.PaymentType,
                    u.FirstName,
                    u.LastName,
                    CameFrom,
                    i.InviterID,
                    c.IncomeTransactionID

            UNION

                SELECT
                    CONCAT('qt-', qt.QsTransactionID) AS CartID,
                    qt.UserID,
                    qt.ProcessDate AS PayDate,
                    " . \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_QSTRANSCATION . " AS PaymentType,
                    u.FirstName,
                    u.LastName,
                    NULL AS CameFrom,
                    NULL AS InviterID,
                    NULL AS IncomeTransactionID,
                    qt.Earnings AS Price,
                    NULL AS OneCardFee
                FROM QsTransaction qt
                JOIN Usr u ON (u.UserID = qt.UserID)
                WHERE
                        qt.UserID IS NOT NULL
                    AND qt.Earnings > 0
                    AND qt.Approvals > 0
                    " . sprintf($fromDate, 'qt.ProcessDate') . "
                    " . sprintf($toDate, 'qt.ProcessDate') . "
                GROUP BY
                        qt.QsTransactionID,
                        qt.UserID,
                        qt.ProcessDate
        ) AS c
        WHERE
                1
                [Filters]
        ";

        if (isset($_POST['calcTotals']) || !empty($_GET['InviterID']) || !empty($_GET['UserID']) || (!empty($_GET['IncomeTransactionID']) && $_GET['IncomeTransactionID'] !== '-')) {
            $list->calcTotals = true;
        }
        $list->ReadOnly = false;
        $list->CanAdd = false;
        $list->AllowDeletes = false;
        $list->ShowFilters = true;
        $list->UsePages = true;
        $list->ShowImport = false;
    }

    public function GetListFields()
    {
        $arFields = parent::GetListFields();
        ArrayInsert($arFields, 'InviterID', false, [
            'IncomeTransactionID' => [
                "Caption" => "Transaction",
                "Type" => "string",
            ],
        ]);

        return $arFields;
    }
}

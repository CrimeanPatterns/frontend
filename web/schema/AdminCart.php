<?php

require_once __DIR__ . "/../lib/schema/BaseAdminCart.php";

class TAdminCartSchema extends TBaseAdminCartSchema
{
    public function __construct()
    {
        parent::TBaseAdminCartSchema();
        $this->ListClass = "TAdminCartList";
    }

    public function GetListFields()
    {
        $arFields = parent::GetListFields();
        unset($arFields['Processed']);
        $arFields["CartID"]["FilterField"] = "c.CartID";

        $memcached = getSymfonyContainer()->get(\Memcached::class);
        $cartItemsListPersist = $memcached->get('manager_admincart_list3');

        if (empty($cartItemsListPersist)) {
            $cartItemsListPersist = [
                102 => 'AwardWallet Plus for 2 months',
                103 => 'AwardWallet Plus for 3 months',
                104 => 'AwardWallet Plus for 6 months ## Upgrade Blog Readers (PRICE=0)',
                10 => 'Account upgrade from regular to AwardWallet Plus, 3 months trial',
                11 => 'Gift from %giverName% %customMessage% ## (refs 19206)',
                12 => 'Discount (global cart item)',
                14 => 'Set up recurring payment of %amount% every %period% months',
                15 => 'AwardWallet Credit - Business',
                16 => 'AwardWallet Plus yearly subscription || 12 months (starting from %startDate%) || 1 year, starting from %startDate% (payment was scheduled, not yet processed)',
                1 => 'Account upgrade from regular to AwardWallet Plus || Extension of AwardWallet Plus',
                201 => 'Award Travel 201 Monthly Subscription (with AwardWallet Plus)',
                202 => 'Award Travel 201 Semi-Annual Subscription (with AwardWallet Plus)',
                203 => 'Award Travel 201 Yearly Subscription (with AwardWallet Plus)',
                2 => 'Donation to AwardWallet.com',
                3 => 'Account upgrade from regular to AwardWallet Plus for 20 years',
                4 => 'Account upgrade from regular to AwardWallet Plus for 1 year (EARLY_SUPPORTER_DISCOUNT = 20)',
                50 => 'Balance Watch Credits',
                5 => 'Payment for %count% users of AwardWallet Business Service (%tariffDesc%). This is a yearly recurring payment. || Payment for %count% users of AwardWallet Business Service (%tariffDesc%). Recurring payment: %amount% (Starting %date%)',
                6 => 'Payment for %count% users of AwardWallet Business Service (%tariffDesc%). This is a yearly recurring payment. || Payment for %count% users of AwardWallet Business Service (%tariffDesc%). Recurring payment: %amount% (Starting %date%)',
                7 => 'OneCard Credits',
                8 => 'AwardWallet OneCard Credit',
                9 => 'Booking',
            ];
            $typeIdWithCartItemId = SQLToArray("
		        SELECT DISTINCT ci.TypeID, MAX(ci.CartItemID) AS CartItemID FROM CartItem ci
                JOIN Cart c ON (c.CartID = ci.CartID)
                JOIN Usr u ON (u.UserID = c.UserID AND u.Language = 'en')
		        WHERE ci.TypeID NOT IN (" . implode(',', array_keys($cartItemsListPersist)) . ")
                GROUP BY ci.TypeID
		    ", 'TypeID', 'CartItemID');

            if (!empty($typeIdWithCartItemId)) {
                $cartItemList = SQLToArray('
                    SELECT TypeID, CONCAT(TypeID, \': \', Name, \' "\', Description, \'"\') AS Name
                    FROM CartItem
                    WHERE CartItemID IN (' . implode(',', $typeIdWithCartItemId) . ')
                    ORDER BY Name ASC
                ', 'TypeID', 'Name');

                $cartItemsListPersist = array_replace($cartItemsListPersist, $cartItemList);
            }
            asort($cartItemsListPersist);

            $cartItemsListPersist = array_map(fn ($item) => trim(strip_tags($item), '" '), $cartItemsListPersist);
            $memcached->set('manager_admincart_list3', $cartItemsListPersist, 86400);
        }

        $arFields["Order"] = [
            // "Type" => "string",
            // "Database" => false,
            'Type' => 'integer',
            'filterWidth' => '100%;max-width:500',
            'Options' => $cartItemsListPersist,
            'FilterField' => 'ci.TypeID',
        ];

        return $arFields;
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);
        $list->ReadOnly = false;
        $list->CanAdd = false;
        $list->AllowDeletes = true;
        $list->ShowExport = false;
        $list->ShowImport = false;
    }

    public function CreateForm()
    {
        DieTrace("Access denied");
    }
}

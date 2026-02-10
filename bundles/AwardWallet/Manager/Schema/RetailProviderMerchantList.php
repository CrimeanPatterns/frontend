<?php

namespace AwardWallet\Manager\Schema;

use Doctrine\DBAL\Connection;

class RetailProviderMerchantList extends \TBaseList
{
    private const TRAVEL_KINDS = [
        PROVIDER_KIND_AIRLINE => true,
        PROVIDER_KIND_HOTEL => true,
        PROVIDER_KIND_CAR_RENTAL => true,
        PROVIDER_KIND_TRAIN => true,
        PROVIDER_KIND_CRUISES => true,
        PROVIDER_KIND_PARKING => true,
    ];
    protected Connection $dbConnection;

    public function __construct($table, $fields, $defaultSort = null, ?\Symfony\Component\HttpFoundation\Request $request = null)
    {
        parent::__construct($table, $fields, $defaultSort, $request);

        $this->dbConnection = getSymfonyContainer()->get('database_connection');
        $this->SQL = '
            SELECT 
                rpm.RetailProviderMerchantID,
                rpm.MerchantID,
                m.Name as MerchantName,
                m.Transactions,
                m.TransactionsLast3Months,
                p.ProviderID,
                p.Kind as ProviderKind,
                p.Accounts as Popularity,
                p.Site,
                rpm.Manual,
                rpm.Auto,
                rpm.ProviderID,
                rpm.Disabled,
                coalesce(scg.Name, sc.Name) as GroupName
            FROM RetailProviderMerchant rpm
            join Merchant m on rpm.MerchantID = m.MerchantID
            join Provider p on rpm.ProviderID = p.ProviderID
            left join ShoppingCategoryGroup scg on m.ShoppingCategoryGroupID = scg.ShoppingCategoryGroupID
            left join ShoppingCategory sc on m.ShoppingCategoryID = sc.ShoppingCategoryID
            where 
                1 = 1 
                [Filters]';
        $this->DefaultSort = 'Popularity';
        $this->InplaceEdit = true;
    }

    public function GetFilterFields()
    {
        $filterFields = parent::GetFilterFields();
        $filterFields['ProviderID']['InputAttributes'] .= " data-form-select2='" . \json_encode(['width' => '300px']) . "'";

        return $filterFields;
    }

    public function FormatFields($output = "html")
    {
        global $arProviderKind;
        parent::FormatFields($output);

        // Merchant
        $scgName = $this->Query->Fields['GroupName'];
        $merchantName = $this->Query->Fields['MerchantName'];
        $transactions = $this->Query->Fields['Transactions'];
        $merchantId = $this->Query->Fields['MerchantID'];
        $providerKind = $this->Query->Fields['ProviderKind'];
        $kindMatchText = "<span title=\"Merchant\">{$scgName}</span> — <span title=\"Provider\">{$arProviderKind[$providerKind]}</span>";

        if (
            isset($scgName)
            && self::isGroupMatched($arProviderKind[$providerKind], $providerKind, $scgName)
        ) {
            $kindMatchText = "<span style='font-weight: bold; color: green'>{$kindMatchText}</span>";
        }

        $this->Query->Fields['ProviderKind'] = $kindMatchText;
        $url = \urlencode($merchantName) . '_' . $merchantId;
        $this->Query->Fields['MerchantID'] = "<a href='/mercchant/{$url}' target='_blank'>{$merchantName}</a>, TRXs: {$transactions}" . (isset($scgName) ? ", G: {$scgName}" : "");
        // Site
        $this->Query->Fields['Site'] = "<a href='{$this->Query->Fields['Site']}' target='_blank'>{$this->Query->Fields['Site']}</a>";
    }

    private static function isGroupMatched(string $providerKindString, int $providerKindInt, ?string $merchantGroup): bool
    {
        if (
            (false !== \stripos($providerKindString, $merchantGroup))
            || (false !== \stripos($merchantGroup, $providerKindString))
        ) {
            return true;
        }

        return false;
    }
}

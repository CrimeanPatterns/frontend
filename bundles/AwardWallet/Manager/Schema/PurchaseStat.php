<?php

namespace AwardWallet\Manager\Schema;

class PurchaseStat extends \TBaseSchema
{
    public function __construct()
    {
        parent::TBaseSchema();

        $this->ListClass = PurchaseStatList::class;
        $this->TableName = 'PurchaseStat';
        $this->Fields = [
            'PurchaseStatID' => [
                'Caption' => 'id',
                'Type' => 'integer',
                'InputAttributes' => 'readonly',
                'Required' => false,
                'ReadOnly' => true,
            ],
            'ProviderID' => [
                'Caption' => 'Provider',
                'Type' => 'integer',
                'InputAttributes' => 'readonly',
                'Options' => ['' => 'All providers'] + SQLToArray('select p.ProviderID, p.DisplayName from Provider p order by p.DisplayName', 'ProviderID', 'DisplayName'),
                'Required' => true,
            ],
            'MinDuration' => [
                'Caption' => 'Min Duration (in hours)',
                'Type' => 'float',
                'Size' => 10,
                'Required' => false,
                'Note' => 'May have decimal part.',
            ],
            'MaxDuration' => [
                'Caption' => 'Max Duration (in hours)',
                'Type' => 'float',
                'Size' => 10,
                'Required' => false,
                'Note' => 'May have decimal part.',
            ],
            'CalcDuration' => [
                'Caption' => 'Calc Duration (in hours)',
                'Type' => 'float',
                'Size' => 10,
                'Required' => false,
                'InputAttributes' => 'readonly',
                'FilterField' => 'CalcDuration/60/60',
            ],
            'TransactionCount' => [
                'Caption' => 'Transaction Count',
                'Type' => 'integer',
                'Size' => 10,
                'InputAttributes' => 'readonly',
            ],
            'TimeDeviation' => [
                'Caption' => 'Time Deviation (in hours)',
                'Type' => 'float',
                'Size' => 10,
                'Required' => false,
                'InputAttributes' => 'readonly',
                'FilterField' => 'TimeDeviation/60/60',
            ],
            'BonusStartDate' => [
                'Caption' => 'Deal Start Date',
                'Type' => 'date',
                'Required' => false,
            ],
            'BonusEndDate' => [
                'Caption' => 'Deal End Date',
                'Type' => 'date',
                'Required' => false,
            ],
            'BonusDescription' => [
                'Caption' => 'Deal Description',
                'Type' => 'string',
                "Size" => 100,
                'Required' => false,
            ],

            'DetailedText' => [
                'Caption' => 'SEO Keyword',
                'Type' => 'string',
                "Size" => 255,
                'Required' => false,
            ],
            'DetailedLink' => [
                'Caption' => 'Internal Link',
                'Type' => 'string',
                "Size" => 255,
                'Required' => false,
            ],
            'OfferLink' => [
                'Caption' => 'Monetized Link',
                'Type' => 'string',
                "Size" => 255,
                'Required' => false,
            ],
        ];
        $this->FilterFields = ['CalcDuration'];
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);
        $q = "
            SELECT
                ps.PurchaseStatID,
                ps.ProviderID,
                round(ps.MinDuration, 1) AS 'MinDuration',
                round(ps.MaxDuration, 1) AS 'MaxDuration',
                round(ps.CalcDuration/60/60, 1) AS 'CalcDuration',
                ps.TransactionCount,
                round(ps.TimeDeviation/60/60, 1) AS 'TimeDeviation',
                ps.BonusStartDate,
                ps.BonusEndDate,
                ps.BonusDescription,
                ps.DetailedText, ps.DetailedLink, ps.OfferLink
            FROM PurchaseStat as ps
        ";
        $list->SQL = $q;
        $list->InplaceEdit = true;

        unset($list->Fields['DetailedText'], $list->Fields['DetailedLink'],$list->Fields['OfferLink']);
    }

    public function GetListFields()
    {
        $arFields = parent::GetListFields();

        foreach ($arFields as $key => $field) {
            $arFields[$key]['InplaceEdit'] = false;
        }

        return $arFields;
    }

    public function TuneForm(\TBaseForm $form)
    {
        parent::TuneForm($form);
        unset($form->Fields['CalcDuration']);
        unset($form->Fields['TransactionCount']);
        unset($form->Fields['TimeDeviation']);
        $form->Uniques = [
            [
                "Fields" => ['ProviderID'],
                "ErrorMessage" => "An entry for this provider already exists.",
            ],
        ];
        $form->OnCheck = [$this, "formCheck", &$form];
        $form->OnSave = [$this, "formCheck", &$form];
    }

    public function formCheck($objForm)
    {
        if (!empty($objForm->Fields['MinDuration']['Value']) && !empty($objForm->Fields['MaxDuration']['Value'])
            and $objForm->Fields['MinDuration']['Value'] > $objForm->Fields['MaxDuration']['Value']) {
            return '"Min Duration" Value is bigger then "Max Duration" Value';
        }

        if (!empty($objForm->Fields['BonusStartDate']['Value']) && !empty($objForm->Fields['BonusEndDate']['Value'])
            and StrToDate($objForm->Fields['BonusStartDate']['Value']) > StrToDate($objForm->Fields['BonusEndDate']['Value'])) {
            return 'Bonus End Date is earlier than Bonus Start Date';
        }

        return null;
    }
}

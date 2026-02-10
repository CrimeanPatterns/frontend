<?php

class TTransferStatSchema extends TBaseSchema
{
    public function __construct()
    {
        parent::TBaseSchema();
        $this->ListClass = TransferStatAdminList::class;
        $this->TableName = 'TransferStat';
        $this->Fields = [
            'TransferStatID' => [
                'Caption' => 'id',
                'Type' => 'integer',
                'InputAttributes' => 'readonly',
                'Required' => false,
                "ReadOnly" => true,
            ],
            'SourceProviderID' => [
                'Caption' => 'Source Provider',
                'Type' => 'integer',
                'InputAttributes' => 'readonly',
                'Options' => ['' => 'All providers'] + SQLToArray('select p.ProviderID, p.DisplayName from Provider p join (select distinct SourceProviderID from TransferStat) ts on ts.SourceProviderID = p.ProviderID order by p.DisplayName', 'ProviderID', 'DisplayName'),
                'Required' => true,
            ],
            'SourceProgramRegion' => [
                'Caption' => 'Source Region',
                'Type' => 'string',
                'Required' => false,
                'InplaceEdit' => false,
                'Nullable' => false,
            ],
            'TargetProviderID' => [
                'Caption' => 'Target Provider',
                'Type' => 'integer',
                'InputAttributes' => 'readonly',
                'Options' => ['' => 'All providers'] + SQLToArray('select p.ProviderID, p.DisplayName from Provider p join (select distinct TargetProviderID from TransferStat) ts on ts.TargetProviderID = p.ProviderID order by p.DisplayName', 'ProviderID', 'DisplayName'),
                'Required' => true,
            ],
            'TargetProgramRegion' => [
                'Caption' => 'Target Region',
                'Type' => 'string',
                'Required' => false,
                'InplaceEdit' => false,
                'Nullable' => false,
            ],
            'SourceRate' => [
                'Caption' => 'Source Rate',
                'Type' => 'integer',
                'Size' => 30,
                'Required' => false,
            ],
            'TargetRate' => [
                'Caption' => 'Target Rate',
                'Type' => 'integer',
                'Size' => 30,
                'Required' => false,
            ],
            'MinimumTransfer' => [
                'Caption' => 'Minimum Transfer',
                'Type' => 'integer',
                'Size' => 30,
                'Required' => false,
            ],
            'MinDuration' => [
                'Caption' => 'Min Duration<br/>(in hours)',
                'Type' => 'float',
                'Size' => 10,
                'Required' => false,
                "Note" => "May have decimal part.",
            ],
            'MaxDuration' => [
                'Caption' => 'Max Duration<br/>(in hours)',
                'Type' => 'float',
                'Size' => 10,
                'Required' => false,
                "Note" => "May have decimal part.",
            ],
            'CalcDuration' => [
                'Caption' => 'Calc Duration<br/>(in hours)',
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
                'Caption' => 'Time Deviation<br/>(in hours)',
                'Type' => 'float',
                'Size' => 10,
                'Required' => false,
                'InputAttributes' => 'readonly',
                'FilterField' => 'TimeDeviation/60/60',
            ],
            'BonusStartDate' => [
                'Caption' => 'Bonus Start Date',
                'Type' => 'date',
                'Required' => false,
            ],
            'BonusEndDate' => [
                'Caption' => 'Bonus End Date',
                'Type' => 'date',
                'Required' => false,
                'Note' => '⬆️ Enter the day after this transfer bonus expires (e.g. 1/16/2025 if the transfer bonus is valid through January 15, 2025)',
            ],
            'BonusPercentage' => [
                'Caption' => 'Bonus Percentage',
                'Type' => 'integer',
                'Size' => 10,
                'Required' => false,
            ],
            'CustomMessage' => [
                'Type' => 'string',
                'Required' => false,
                'InputType' => 'textarea',
                'Size' => 2048,
                'HTML' => true,
            ],
        ];

        if (!empty($_GET['SourceProviderID'])) {
            $mileValueService = getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\MileValue\MileValueService::class);
            $mileValueItem = $mileValueService->getProviderItem((int) $_GET['SourceProviderID']);

            if (!empty($mileValueItem)) {
                ArrayInsert(
                    $this->Fields,
                    'TargetRate',
                    true,
                    [
                        'PointValue' => [
                            'Type' => 'string',
                            'HTML' => true,
                        ],
                    ]);
            }
        }
        $this->FilterFields = ['SourceProvider'];
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);
        $q = "
				SELECT
					ts.TransferStatID,
					ts.SourceProviderID AS 'SourceProviderID',
					ts.TargetProviderID AS 'TargetProviderID',
					ts.TargetProviderID,
					ts.SourceRate,
					ts.TargetRate,
					round(ts.MinDuration, 1) AS 'MinDuration',
					round(ts.MaxDuration, 1) AS 'MaxDuration',
					round(ts.CalcDuration/60/60, 1) AS 'CalcDuration',
					ts.TransactionCount,
					round(ts.TimeDeviation/60/60, 1) AS 'TimeDeviation',
					ts.BonusStartDate,
                    ts.BonusEndDate,
                    ts.BonusPercentage,
                    ts.CustomMessage,
                    ts.MinimumTransfer,
                    ts.SourceProgramRegion, ts.TargetProgramRegion
				FROM TransferStat as ts
			";
        $list->SQL = $q;
        $list->InplaceEdit = true;
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
        unset($form->Fields['PointValue']);

        $providers = ['' => 'All providers'] + SQLToArray('select p.ProviderID, p.DisplayName from Provider p order by p.DisplayName asc', 'ProviderID', 'DisplayName');
        $form->Fields['SourceProviderID']['Options'] = $providers;
        $form->Fields['TargetProviderID']['Options'] = $providers;

        $form->Uniques = [
            [
                "Fields" => ['SourceProviderID', 'TargetProviderID', 'SourceProgramRegion', 'TargetProgramRegion'],
                "ErrorMessage" => "An entry for this pair of providers already exists.",
            ],
        ];
        $form->OnCheck = [$this, "formCheck", &$form];
        $form->OnSave = [$this, "formCheck", &$form];
    }

    public function formCheck($objForm)
    {
        if (!empty($objForm->Fields['MinDuration']['Value']) && !empty($objForm->Fields['MaxDuration']['Value'])
            and $objForm->Fields['MinDuration']['Value'] > $objForm->Fields['MaxDuration']['Value']) {
            return '"Min Duration" Value is bigger than "Max Duration" Value';
        }

        if (!empty($objForm->Fields['BonusStartDate']['Value'])
            && !empty($objForm->Fields['BonusEndDate']['Value'])
            && strtotime($objForm->Fields['BonusStartDate']['Value']) > strtotime($objForm->Fields['BonusEndDate']['Value'])
        ) {
            return 'Bonus End Date is earlier than Bonus Start Date';
        }

        return null;
    }

    public function GetImportKeyFields()
    {
        return ['SourceProviderID', 'TargetProviderID', 'SourceRate'];
    }
}

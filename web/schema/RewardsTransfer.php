<?php

class TRewardsTransferSchema extends TBaseSchema
{
    public function TRewardsTransferSchema()
    {
        parent::TBaseSchema();
        $this->TableName = "RewardsTransfer";
        $this->Fields = [
            "RewardsTransferID" => [
                "Caption" => "id",
                "Type" => "integer",
                "InputAttributes" => "readonly",
                "Required" => true,
            ],
            "SourceProviderID" => [
                "Caption" => "Source Provider",
                "Type" => "integer",
                "InputAttributes" => "readonly",
                "Options" => ["" => "All providers"] + SQLToArray("select ProviderID, DisplayName from Provider order by DisplayName", "ProviderID", "DisplayName"),
                "Required" => true,
            ],
            "TargetProviderID" => [
                "Caption" => "Target Provider",
                "Type" => "integer",
                "InputAttributes" => "readonly",
                "Options" => ["" => "All providers"] + SQLToArray("select ProviderID, DisplayName from Provider order by DisplayName", "ProviderID", "DisplayName"),
                "Required" => true,
            ],
            "SourceProvider" => [
                "Caption" => "Source Provider",
                "Type" => "string",
                "InputAttributes" => "readonly",
                "Required" => true,
                "Sort" => "SourceProvider, TargetProvider",
                "Size" => 80,
                "Database" => false,
            ],
            "TargetProvider" => [
                "Caption" => "Target Provider",
                "Type" => "string",
                "InputAttributes" => "readonly",
                "Required" => true,
                "Size" => 80,
                "Database" => false,
            ],
            "SourceRate" => [
                "Caption" => "Source Rate",
                "Type" => "integer",
                "Size" => 5,
                "Required" => true,
            ],
            "TargetRate" => [
                "Caption" => "Target Rate",
                "Type" => "integer",
                "Size" => 5,
                "Required" => true,
            ],
            "Enabled" => [
                "Type" => "integer",
                "Caption" => "Enabled",
                "Required" => true,
                "Value" => 0,
                "InputType" => "checkbox",
            ],
            "Tested" => [
                "Type" => "integer",
                "Caption" => "Tested",
                "Required" => true,
                "Value" => 0,
                "InputType" => "checkbox",
            ],
            "TransferDuration" => [
                "Caption" => "Transfer Duration",
                "Type" => "string",
                "Size" => 80,
                "Required" => false,
            ],
            "Comment" => [
                "Type" => "string",
                "Caption" => "Comment",
                "InputType" => "textarea",
            ],
        ];
        $this->FilterFields = ["SourceProvider"];
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);
        $q = "
				SELECT
					rt.RewardsTransferID,
					rt.SourceProviderID AS 'SourceProviderID',
					rt.TargetProviderID AS 'TargetProviderID',
					p1.DisplayName AS 'SourceProvider',
					p2.DisplayName AS 'TargetProvider',
					rt.TargetProviderID,
					rt.SourceRate,
					rt.TargetRate,
					rt.Enabled,
					rt.TransferDuration,
					rt.Tested,
					rt.Comment
				FROM RewardsTransfer as rt
				JOIN Provider as p1
					ON rt.SourceProviderID = p1.ProviderID
				JOIN Provider as p2
				 	ON rt.TargetProviderID = p2.ProviderID
			";
        $list->SQL = $q;
        unset($list->Fields['SourceProvider']);
        unset($list->Fields['TargetProvider']);
        $list->InplaceEdit = true;
    }

    public function GetListFields()
    {
        $arFields = parent::GetListFields();

        foreach ($arFields as $key => $field) {
            $arFields[$key]["InplaceEdit"] = false;
        }
        $arFields['Enabled']['InplaceEdit'] = true;
        $arFields['Tested']['InplaceEdit'] = true;

        return $arFields;
    }

    public function TuneForm(TBaseForm $form)
    {
        parent::TuneForm($form);
        $container = getSymfonyContainer();
        $rewardsRepository = $container->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\RewardsTransfer::class);
        $currentReward = $rewardsRepository->find(ArrayVal($_GET, 'ID', 0));

        foreach (['Source', 'Target'] as $prefix) {
            $form->Fields[$prefix . 'ProviderID']['Caption'] = $prefix . ' Provider';
        }

        if ($currentReward) {
            // Edit existing rewards transfer
            $form->Fields['RewardsTransferID']['Required'] = false;
            $arr = [
                'Source' => [
                    'id' => $currentReward->getSourceProvider()->getProviderid(),
                    'displayName' => $currentReward->getSourceProvider()->getDisplayname(),
                ],
                'Target' => [
                    'id' => $currentReward->getTargetProvider()->getProviderid(),
                    'displayName' => $currentReward->getTargetProvider()->getDisplayname(),
                ],
            ];

            foreach (['Source', 'Target'] as $prefix) {
                unset($form->Fields[$prefix . 'ProviderID']);
                $form->Fields[$prefix . 'Provider']['Required'] = false;
                $form->Fields[$prefix . 'Provider']['Value'] = $arr[$prefix]['displayName'];
                $form->SQLParams[$prefix . 'ProviderID'] = $arr[$prefix]['id'];
            }

            foreach (['RewardsTransferID', 'SourceProvider', 'TargetProvider'] as $key) {
                $form->Fields[$key]['InputAttributes'] = 'style="background-color: #dfdfdf; background-image: none;" readonly';
            }
        } else {
            // Create new rewards transfer
            unset($form->Fields['RewardsTransferID']);

            //			$options =
            //				[null => 'Choose provider'] + SQLToArray("SELECT ProviderID, DisplayName FROM Provider ORDER BY DisplayName", "ProviderID", "DisplayName");
            foreach (['Source', 'Target'] as $prefix) {
                unset($form->Fields[$prefix . 'Provider']);
                //				$objForm->Fields[$prefix.'ProviderID']['Options'] = $options;
            }

            // Add unique key check
            $form->Uniques = [
                [
                    'Fields' => ['SourceProviderID', 'TargetProviderID', 'SourceRate'],
                    'ErrorMessage' => 'Rewards transfer with such source provider, target provider, source rate already exists. Set one of this fields to another value or edit existing rewards transfer.',
                ],
            ];
        }
    }

    public function GetImportKeyFields()
    {
        return ["SourceProviderID", "TargetProviderID", "SourceRate"];
    }
}

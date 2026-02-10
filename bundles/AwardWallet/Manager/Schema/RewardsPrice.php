<?php

namespace AwardWallet\Manager\Schema;

use AwardWallet\MainBundle\Service\MileValue\Constants;

class RewardsPrice extends \TBaseSchema
{
    public function GetFormFields()
    {
        $fields = parent::GetFormFields();

        $fields['MileCost'] = [
            "Type" => "string",
            "Manager" => $this->getMileCostFieldManager(),
        ];

        $fields['AwardChartID']['InputAttributes'] = ' onchange="this.form.DisableFormScriptChecks.value=1; ;this.form.submit();"';
        // @TODO: alter database field, and remove line below, after filling in blanks
        $fields['AwardChartID']['Required'] = true;

        return $fields;
    }

    public function TuneForm(\TBaseForm $form)
    {
        parent::TuneForm($form);

        $form->OnLoaded = function () use ($form) {
            $awardChartId = $form->Fields['AwardChartID']['Value'];

            if ($awardChartId !== null) {
                foreach (['FromRegionID', 'ToRegionID'] as $regionField) {
                    $form->Fields[$regionField]['Options'] = ["" => ""] + SQLToArray("select RegionID, Name from Region where Kind = " . REGION_KIND_AIRLINE_REGION . " and AwardChartID = " . (int) $awardChartId . " order by Name", "RegionID", "Name");
                }
            }
        };
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);

        $list->showCopy = true;
    }

    protected function guessFieldOptions(string $field, array $fieldInfo): ?array
    {
        if ($field === 'FromRegionID' || $field === 'ToRegionID') {
            return ["" => ""] + SQLToArray("select RegionID, Name from Region where Kind = " . REGION_KIND_AIRLINE_REGION . " order by Name",
                "RegionID", "Name");
        }

        if ($field === 'DistanceUnit') {
            return [
                '' => '',
                'M' => 'Miles',
                'K' => 'Km',
            ];
        }

        return parent::guessFieldOptions($field, $fieldInfo);
    }

    private function getMileCostFieldManager(): \TTableLinksFieldManager
    {
        $manager = new \TTableLinksFieldManager();
        $manager->TableName = "RewardsPriceMileCost";
        $manager->Fields = [
            "MileCost" => [
                "Type" => "integer",
                "Required" => true,
            ],
            "TicketClass" => [
                "Type" => "integer",
                "Required" => true,
                "Options" => ["" => ""] + Constants::CLASSES_OF_SERVICE,
            ],
            'AwardTypeID' => [
                "Type" => "string",
                "Required" => true,
                "Options" => ["" => ""] + SQLToArray("select AwardTypeID, Name from AwardType order by Name", "AwardTypeID", "Name"),
            ],
            'AwardSeasonID' => [
                "Type" => "string",
                "Required" => false,
                "Options" => ["" => ""] + SQLToArray("select AwardSeasonID, Name from AwardSeason order by Name", "AwardSeasonID", "Name"),
            ],
            'RoundTrip' => [
                'Type' => "boolean",
                "Required" => true,
            ],
        ];
        $manager->CanEdit = true;
        $manager->AutoSave = true;

        return $manager;
    }
}

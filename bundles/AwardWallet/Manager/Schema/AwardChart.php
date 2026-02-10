<?php

namespace AwardWallet\Manager\Schema;

class AwardChart extends \TBaseSchema
{
    public function __construct()
    {
        parent::__construct();
        $this->Fields['ProviderID']['Caption'] = 'Mile Currency';
        // TODO: убрать после миграции в not null
        $this->Fields['ProviderID']['Required'] = true;
    }

    public function GetFormFields()
    {
        $fields = parent::GetFormFields();
        $fields['Airlines'] = [
            "Type" => "string",
            "Caption" => "Airline Groups",
            "Manager" => $this->getAirlinesGroupsFieldManager(),
        ];

        return $fields;
    }

    protected function guessFieldOptions(string $field, array $fieldInfo): ?array
    {
        if ($field === 'ProviderID') {
            return SQLToArray("select ProviderID, DisplayName from Provider where State >= " . PROVIDER_ENABLED . " order by DisplayName",
                "ProviderID", "DisplayName");
        }

        return parent::guessFieldOptions($field, $fieldInfo);
    }

    private function getAirlinesGroupsFieldManager(): \TTableLinksFieldManager
    {
        $manager = new \TTableLinksFieldManager();
        $manager->TableName = "AwardChartAirlineGroup";
        $manager->Fields = [
            "AirlineGroupID" => [
                "Type" => "integer",
                "Required" => true,
                "Options" => ["" => ""] + SQLToArray("select AirlineGroupID as ID, Name from AirlineGroup order by Name", "ID", "Name"),
            ],
            "MinV" => [
                "Type" => "integer",
                "Caption" => "Min",
            ],
            "MaxV" => [
                "Type" => "integer",
                "Caption" => "Max",
            ],
        ];
        $manager->CanEdit = true;
        $manager->AutoSave = true;

        return $manager;
    }
}

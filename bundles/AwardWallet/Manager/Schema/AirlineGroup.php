<?php

namespace AwardWallet\Manager\Schema;

class AirlineGroup extends \TBaseSchema
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
            "Caption" => "Operating Airlines",
            "Manager" => $this->getAirlinesFieldManager(),
        ];

        return $fields;
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);

        $list->showCopy = true;
    }

    protected function guessFieldOptions(string $field, array $fieldInfo): ?array
    {
        if ($field === 'ProviderID') {
            return SQLToArray("select ProviderID, DisplayName from Provider where State >= " . PROVIDER_ENABLED . " order by DisplayName",
                "ProviderID", "DisplayName");
        }

        return parent::guessFieldOptions($field, $fieldInfo);
    }

    private function getAirlinesFieldManager(): \TTableLinksFieldManager
    {
        $manager = new \TTableLinksFieldManager();
        $manager->TableName = "AirlineGroupAirline";
        $manager->Fields = [
            "AirlineID" => [
                "Type" => "integer",
                "Required" => true,
                "Options" => ["" => ""] + SQLToArray("select AirlineID as ID, coalesce(concat(Code, ', ', Name), Name) as Name from Airline where Active = 1 order by coalesce(concat(Code, ', ', Name), Name)", "ID", "Name"),
            ],
        ];
        $manager->CanEdit = true;
        $manager->AutoSave = true;

        return $manager;
    }
}

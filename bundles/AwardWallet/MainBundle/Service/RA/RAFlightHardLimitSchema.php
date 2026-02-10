<?php

namespace AwardWallet\MainBundle\Service\RA;

class RAFlightHardLimitSchema extends \TBaseSchema
{
    public function __construct()
    {
        parent::__construct();
        $this->TableName = "RAFlightHardLimit";
        $this->Fields = [
            "RAFlightHardLimitID" => [
                "Type" => "integer",
                "Caption" => "ID",
                "Size" => 10,
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 20,
                "Sort" => "RAFlightHardLimitID DESC",
            ],
            "ProviderID" => [
                "Caption" => "Provider",
                "Type" => "integer",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "Options" =>
                    SQLToArray(/** @lang MySQL */ "select p.ProviderID, p.DisplayName from Provider p 
                    where (p.CanCheckRewardAvailability <> 0) 
                    order by DisplayName",
                        "ProviderID", "DisplayName"),
                "FilterField" => "RAFlightHardLimit.ProviderID",
            ],
            "ClassOfService" => [
                "Type" => "string",
                "Size" => 40,
                "Required" => true,
                "Options" => [
                    'economy' => 'economy',
                    'premiumEconomy' => 'premiumEconomy',
                    'business' => 'business',
                    'firstClass' => 'firstClass',
                ],
                "ReadOnly" => true,
                "filterWidth" => 30,
            ],
            "Base" => [
                "Type" => "integer",
                "Required" => true,
                "InplaceEdit" => false,
            ],
            "Multiplier" => [
                "Type" => "integer",
                "Required" => true,
                "InplaceEdit" => false,
            ],
            "HardCap" => [
                "Type" => "integer",
                "Required" => true,
                "InplaceEdit" => false,
            ],
        ];
        $this->bIncludeList = false;
        $this->ListClass = RAFlightHardLimitList::class;
    }

    public function GetListFields()
    {
        $result = parent::GetListFields();

        foreach ($result as $field => $params) {
            $result[$field]['FilterField'] = 'RAFlightHardLimit.' . $field;
        }

        return $result;
    }
}

<?php

namespace AwardWallet\MainBundle\Service\RA;

class RAFlightSegmentSchema extends \TBaseSchema
{
    public function __construct()
    {
        parent::__construct();
        $this->TableName = "RAFlightSegment";
        $this->Fields = [
            "RAFlightSegmentID" => [
                "Type" => "integer",
                "Caption" => "ID",
                "Size" => 10,
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 20,
                "Sort" => "RAFlightSegmentID DESC",
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
                "FilterField" => "RAFlightSegment.ProviderID",
            ],
            "DepartureAirport" => [
                "Type" => "string",
                "Size" => 10,
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 50,
            ],
            "ArrivalAirport" => [
                "Type" => "string",
                "Size" => 10,
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 50,
            ],
            "Airline" => [
                "Type" => "string",
                "Size" => 10,
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 50,
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
            "LastParsedDate" => [
                "Type" => "datetime",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 50,
            ],
            "TimesSeen" => [
                "Type" => "integer",
                "Required" => true,
                "InplaceEdit" => false,
            ],
        ];
        $this->bIncludeList = false;
    }

    public function GetListFields()
    {
        $result = parent::GetListFields();

        $result['LastParsedDate'] = [
            'Type' => 'datetime',
        ];

        foreach ($result as $field => $params) {
            $result[$field]['FilterField'] = 'RAFlightSegment.' . $field;
        }

        return $result;
    }
}

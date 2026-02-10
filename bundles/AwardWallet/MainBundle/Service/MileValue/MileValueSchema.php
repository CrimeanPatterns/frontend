<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\MainBundle\Entity\Provider;

class MileValueSchema extends \TBaseSchema
{
    public function __construct()
    {
        parent::__construct();
        $this->TableName = "MileValue";
        $this->Fields = [
            "MileValueID" => [
                "Type" => "integer",
                "Caption" => "ID",
                "Size" => 10,
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 20,
                "Sort" => "MileValueID DESC",
            ],
            "ProviderID" => [
                "Caption" => "Provider",
                "Type" => "integer",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "Options" => SQLToArray("select p.ProviderID, p.DisplayName from Provider p 
                    where (p.State > 0 or p.State = " . PROVIDER_HIDDEN . " or p.State = " . PROVIDER_TEST . " or p.ProviderID = " . Provider::AA_ID . ") 
                    order by DisplayName",
                    "ProviderID", "DisplayName"),
                "FilterField" => "MileValue.ProviderID",
            ],
            "PMVStatus" => [
                "Caption" => "Provider Enabled",
                "Type" => "boolean",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "FilterField" => "coalesce(pmv.Status, 0)",
            ],
            "MileAirlines" => [
                "Type" => "string",
                "Size" => 10,
                "Required" => false,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 50,
            ],
            "CashAirlines" => [
                "Type" => "string",
                "Size" => 10,
                "Required" => false,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 50,
            ],
            "RecordLocator" => [
                "Type" => "string",
                "InplaceEdit" => false,
                "FilterField" => "Trip.RecordLocator",
                "filterWidth" => 50,
            ],
            "TripID" => [
                "Type" => "integer",
                "Caption" => "Trip ID",
                "InplaceEdit" => false,
                "FilterField" => "MileValue.TripID",
            ],
            "UserID" => [
                "Type" => "integer",
                "Caption" => "User ID",
                "InplaceEdit" => false,
            ],
            "Route" => [
                "Type" => "string",
                "Size" => 10,
                "Required" => false,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 40,
            ],
            "RouteType" => [
                "Type" => "string",
                "Size" => 3,
                "Required" => false,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 20,
            ],
            "International" => [
                "Caption" => "Global",
                "Type" => "boolean",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 20,
            ],
            "MileRoute" => [
                "Type" => "string",
                "Size" => 10,
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 40,
            ],
            "CashRoute" => [
                "Type" => "string",
                "Size" => 10,
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 40,
            ],
            "BookingClasses" => [
                "Type" => "string",
                "Size" => 10,
                "Required" => false,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 20,
            ],
            "CabinClass" => [
                "Type" => "string",
                "Size" => 10,
                "Required" => false,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 40,
                "FilterField" => "MileValue.CabinClass",
            ],
            "ClassOfService" => [
                "Type" => "string",
                "Size" => 10,
                "Required" => false,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 30,
            ],
            "DepDate" => [
                "Type" => "datetime",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 50,
            ],
            "ReturnDate" => [
                "Type" => "datetime",
                "Required" => false,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "AllowFilters" => false,
            ],
            "MileDuration" => [
                "Type" => "float",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 20,
            ],
            "CashDuration" => [
                "Type" => "float",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 20,
            ],
            "CreateDate" => [
                "Type" => "datetime",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 50,
                "FilterField" => "MileValue.CreateDate",
                "Sort" => "MileValue.CreateDate DESC",
            ],
            "UpdateDate" => [
                "Type" => "datetime",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 50,
                "FilterField" => "MileValue.UpdateDate",
            ],
            "TravelersCount" => [
                "Type" => "integer",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => false,
                "filterWidth" => 20,
                "InputAttributes",
            ],
            "TotalMilesSpent" => [
                "Type" => "integer",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => false,
                "filterWidth" => 20,
            ],
            "MilesSource" => [
                "Type" => "string",
                "Required" => false,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 70,
                "Options" => \AwardWallet\MainBundle\Service\MileValue\Constants::MILE_SOURCES,
            ],
            "TotalTaxesSpent" => [
                "Type" => "float",
                "Caption" => "Total Cash Spent",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => false,
                "filterWidth" => 20,
            ],
            "AlternativeCost" => [
                "Type" => "float",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => false,
                "filterWidth" => 20,
            ],
            "KiwiMinPrice" => [
                "Type" => "float",
                "Required" => false,
                "InplaceEdit" => false,
                "ReadOnly" => false,
                "filterWidth" => 20,
            ],
            "SkyscannerMinPrice" => [
                "Type" => "float",
                "Required" => false,
                "InplaceEdit" => false,
                "ReadOnly" => false,
                "filterWidth" => 20,
            ],
            "PriceAdjustment" => [
                "Type" => "float",
                "Required" => false,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 20,
            ],
            "MileValue" => [
                "Type" => "float",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 20,
            ],
            'SourceCheck' => [
                'Type' => 'string',
                'ReadOnly' => true,
                'Required' => false,
                'Database' => false,
            ],
            "Status" => [
                "Type" => "string",
                "Required" => true,
                "InplaceEdit" => true,
                "ReadOnly" => true,
                "filterWidth" => 50,
                "FilterField" => "MileValue.Status",
                "Options" => \AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand::STATUSES,
            ],
            "Note" => [
                "Type" => "string",
                "Required" => false,
                "InplaceEdit" => true,
                "ReadOnly" => false,
                "filterWidth" => 130,
                "InputType" => "textarea",
                "InputAttributes" => "style='height: 50px; width: 130px;'",
            ],
        ];
        $this->bIncludeList = false;
        $this->ListClass = \AwardWallet\MainBundle\Service\MileValue\MileValueList::class;
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);
        $list->MultiEdit = true;
        $list->Fields["MileValueID"]["filterWidth"] = 20;
        $list->Fields["PMVStatus"]["filterWidth"] = 20;
        $list->Fields["BookingClasses"]["Caption"] = "Booking<br>Classes";
        $list->Fields["CabinClass"]["Caption"] = "Cabin<br>Class";
        $list->Fields["ClassOfService"]["Caption"] = "Class of<br>Service";
        $list->Fields["MileDuration"]["Caption"] = "Mile<br/>Duration";
        $list->Fields["CashDuration"]["Caption"] = "Cash<br/>Duration";
        $list->Fields["TotalMilesSpent"]["Caption"] = "Total Miles<br/>Spent";
        $list->Fields["TotalTaxesSpent"]["Caption"] = "Total Cash<br/>Spent";
        $list->Fields["KiwiMinPrice"]["Caption"] = "Kiwi<br/>Min Price";
        $list->Fields["SkyscannerMinPrice"]["Caption"] = "SkyScanner<br/>Min Price";
        $list->Fields["AlternativeCost"]["Caption"] = "Alternative<br/>Cost";
        $list->Fields["PriceAdjustment"]["Caption"] = "Price<br/>Adjustment";
        $list->Fields["MileValue"]["Caption"] = "Mile<br/>Value";
        $list->Fields["RouteType"]["Caption"] = "Route<br/>Type";
        $list->Fields["TravelersCount"]["Caption"] = "# of<br/>Pax";
        $list->InplaceEdit = true;
        $list->CanAdd = false;
        $list->repeatHeadersEveryNthRow = 10;
    }

    public function GetFormFields()
    {
        $result = parent::GetFormFields();
        $result = array_intersect_key($result, array_flip(['TravelersCount', 'AlternativeCost', 'TotalMilesSpent', 'TotalTaxesSpent', 'Status', 'Note']));
        $result['TravelersCount']['Cols'] = 3;
        $result['TotalMilesSpent']['Cols'] = 10;
        $result['TotalTaxesSpent']['Cols'] = 5;
        $result['AlternativeCost']['Cols'] = 7;

        return $result;
    }

    public function TuneForm(\TBaseForm $form)
    {
        parent::TuneForm($form);
        $form->OnSave = function () use ($form) {
            $connection = getSymfonyContainer()->get("database_connection");

            $oldRow = $connection->fetchAssoc("select MileValue, TotalMilesSpent from MileValue where MileValueID = ?", [$form->ID]);
            $old = array_map(function (array $field) { return $field['OldValue']; }, $form->Fields);
            $old["MileValue"] = $oldRow["MileValue"];

            $new = array_map(function (array $field) { return $field['Value']; }, $form->Fields);
            $new["MileValue"] = MileValueCalculator::calc($new["AlternativeCost"], $new["TotalTaxesSpent"], $oldRow["TotalMilesSpent"]);

            $changedFields = getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\MileValue\HistoryWriter::class)->saveHistory($form->ID, $old, $new);

            $params = ["MileValue" => $new["MileValue"]];

            foreach (array_intersect($changedFields, \AwardWallet\MainBundle\Service\MileValue\Constants::CUSTOM_FIELDS) as $field) {
                $params["Custom" . $field] = "1";
            }
            $connection->update("MileValue", $params, ["MileValueID" => $form->ID]);
        };
    }
}

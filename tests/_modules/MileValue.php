<?php

namespace Codeception\Module;

use AwardWallet\MainBundle\Entity\ProviderMileValue;
use AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand;
use AwardWallet\MainBundle\Service\MileValue\ProviderMileValueItem;
use Codeception\Module;

class MileValue extends Module
{
    public function createAwMileValue(int $providerId, array $fields = []): int
    {
        /** @var CustomDb $db */
        $db = $this->getModule('CustomDb');

        return $db->haveInDatabase(
            'MileValue',
            array_merge([
                'ProviderID' => $providerId,
                "Route" => "FAKE",
                "International" => 1,
                "MileRoute" => "FAKE",
                "CashRoute" => "FAKE",
                "BookingClasses" => "FAKE",
                "CabinClass" => "FAKE",
                "ClassOfService" => "Economy",
                "DepDate" => date("Y") . '-01-01',
                "MileDuration" => 100,
                "CashDuration" => 100,
                "Hash" => "FAKE",
                "CreateDate" => date("Y") . '-01-01',
                "UpdateDate" => date("Y") . '-01-01',
                "TotalMilesSpent" => 10000,
                "TotalTaxesSpent" => 10,
                "AlternativeCost" => 100,
                "MileValue" => 1.01,
                "RouteType" => "OW",
                "Status" => CalcMileValueCommand::STATUS_GOOD,
            ], $fields)
        );
    }

    public function fillAwMileValue(int $providerId, array $providerMileValueFields = [], int $mileValueRecords = ProviderMileValueItem::MIN_COUNT)
    {
        /** @var CustomDb $db */
        $db = $this->getModule('CustomDb');
        $db->haveInDatabase("ProviderMileValue", array_merge([
            "ProviderID" => $providerId,
            "Status" => ProviderMileValue::STATUS_ENABLED,
        ], $providerMileValueFields));

        for ($n = 0; $n < $mileValueRecords; $n++) {
            $this->createAwMileValue($providerId, ["ClassOfService" => "Economy", "International" => 0]);
            $this->createAwMileValue($providerId, ["ClassOfService" => "Economy", "International" => 1]);
            $this->createAwMileValue($providerId, ["ClassOfService" => "Business", "International" => 0]);
            $this->createAwMileValue($providerId, ["ClassOfService" => "Business", "International" => 1]);
        }
    }
}

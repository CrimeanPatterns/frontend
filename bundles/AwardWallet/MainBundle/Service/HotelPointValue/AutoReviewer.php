<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\Entity\HotelPointValue;
use AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand;

class AutoReviewer
{
    private DeviationCalculator $deviationCalculator;

    public function __construct(DeviationCalculator $deviationCalculator)
    {
        $this->deviationCalculator = $deviationCalculator;
    }

    /**
     * @return string|null - note as string if item should be marked for review, or null if item is good
     */
    public function check(HotelPointValue $hpv): ?string
    {
        if (in_array($hpv->getStatus(), CalcMileValueCommand::EXCLUDED_STATUSES)) {
            return null;
        }

        $brand = $hpv->getBrand();

        if ($brand === null) {
            return null;
        }

        $deviationResult = $this->deviationCalculator->calcDeviationParams($brand->getId());

        if ($deviationResult->getAverage() === null) {
            return null;
        }

        $delta = abs($deviationResult->getAverage() - $hpv->getPointValue());

        if ($delta > 0.0005 && $delta > $deviationResult->getDeviation()) {
            $result = "deviation " . round($deviationResult->getDeviation(), 3) . ",  average: " . round($deviationResult->getAverage(), 2) . ", delta: " . round($delta, 2) . ", based on: {$deviationResult->getBasedOnRecords()} records, brand: {$brand->getName()}";

            return $result;
        }

        return null;
    }
}

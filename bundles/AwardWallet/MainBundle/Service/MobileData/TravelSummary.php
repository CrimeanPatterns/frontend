<?php

namespace AwardWallet\MainBundle\Service\MobileData;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\TravelSummary\PeriodDatesHelper;
use AwardWallet\MainBundle\Service\TravelSummary\TravelSummaryService;

class TravelSummary
{
    private TravelSummaryService $travelSummary;
    private PeriodDatesHelper $periodDatesHelper;

    public function __construct(
        TravelSummaryService $travelSummary,
        PeriodDatesHelper $periodDatesHelper
    ) {
        $this->travelSummary = $travelSummary;
        $this->periodDatesHelper = $periodDatesHelper;
    }

    public function getData(Usr $user): array
    {
        $datesResult = $this->periodDatesHelper->getDates(PeriodDatesHelper::YEAR_TO_DATE);
        $flightStat =
            $this->travelSummary
                ->buildPeriodSummary($user, null, $datesResult->getStartDate(), $datesResult->getEndDate(), true)
                ->getTravelStatistics()
                ->getFlightStats();

        return [
            'totalFlights' => [
                'value' => $flightStat->getTotalFlights(),
            ],
            'longHaulFlights' => [
                'value' => $flightStat->getLongHaulFlights(),
                'percentage' => $flightStat->getLongHaulPercentage(),
            ],
            'shortHaulFlights' => [
                'value' => $flightStat->getShortHaulFlights(),
                'percentage' => $flightStat->getShortHaulPercentage(),
            ],
        ];
    }
}

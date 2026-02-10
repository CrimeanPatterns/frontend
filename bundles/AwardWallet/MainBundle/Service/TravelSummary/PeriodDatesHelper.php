<?php

namespace AwardWallet\MainBundle\Service\TravelSummary;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\Tags;
use AwardWallet\MainBundle\Service\TravelSummary\Data\PeriodDatesResult;
use AwardWallet\MainBundle\Service\TravelSummary\DataProvider\Flight;
use AwardWallet\MainBundle\Service\TravelSummary\DataProvider\Hotel;
use AwardWallet\MainBundle\Service\TravelSummary\DataProvider\Parking;
use AwardWallet\MainBundle\Service\TravelSummary\DataProvider\Rental;
use AwardWallet\MainBundle\Service\TravelSummary\DataProvider\Restaurant;
use AwardWallet\MainBundle\Service\TravelSummary\DataProvider\Trip;
use Clock\ClockInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PeriodDatesHelper
{
    public const YEAR_TO_DATE = 1;
    public const LAST_YEAR = 2;
    public const LAST_3_YEARS = 3;
    public const LAST_5_YEARS = 4;
    public const LAST_10_YEARS = 5;
    public const ALL_TIME = 10;

    private Flight $flightProvider;
    private Hotel $hotelProvider;
    private Parking $parkingProvider;
    private Rental $rentalProvider;
    private Restaurant $restaurantProvider;
    private Trip $tripProvider;
    private ClockInterface $clock;
    private TranslatorInterface $translator;
    private CacheManager $cacheManager;

    public function __construct(
        Flight $flightProvider,
        Hotel $hotelProvider,
        Parking $parkingProvider,
        Rental $rentalProvider,
        Restaurant $restaurantProvider,
        Trip $tripProvider,
        ClockInterface $clock,
        TranslatorInterface $translator,
        CacheManager $cacheManager
    ) {
        $this->flightProvider = $flightProvider;
        $this->hotelProvider = $hotelProvider;
        $this->parkingProvider = $parkingProvider;
        $this->rentalProvider = $rentalProvider;
        $this->restaurantProvider = $restaurantProvider;
        $this->tripProvider = $tripProvider;
        $this->clock = $clock;
        $this->translator = $translator;
        $this->cacheManager = $cacheManager;
    }

    /**
     * Get a list of years for which there are reservations for the specified user.
     */
    public function getYears(Usr $user, ?Useragent $userAgent = null): array
    {
        $cacheReference = new CacheItemReference(
            'travel_summary_available_periods',
            Tags::getTimelineCounterTags($user->getId()),
            function () use ($user, $userAgent) {
                $owner = new Owner($user, $userAgent);
                $statistics = [
                    $this->flightProvider->getPeriods($owner),
                    $this->hotelProvider->getPeriods($owner),
                    $this->parkingProvider->getPeriods($owner),
                    $this->rentalProvider->getPeriods($owner),
                    $this->restaurantProvider->getPeriods($owner),
                    $this->tripProvider->getPeriods($owner),
                ];
                $years = [];

                foreach ($statistics as $periods) {
                    foreach (array_keys($periods) as $period) {
                        if (!in_array($period, $years)) {
                            $years[] = $period;
                        }
                    }
                }

                arsort($years, SORT_NUMERIC);

                return $years;
            }
        );

        return $this->cacheManager->load($cacheReference) ?? [];
    }

    /**
     * Get the start and end dates for selecting reservations based on period.
     *
     * @param int $period period from a list of predefined constants (`self::getMainPeriods()`) or a selected year
     */
    public function getDates(int $period, ?Usr $user = null, ?Useragent $userAgent = null): PeriodDatesResult
    {
        $endDate = $this->clock->current()->getAsDateTime();
        $currentYear = (int) $endDate->format('Y');

        if ($period === self::LAST_YEAR) {
            $endDate = new \DateTime(sprintf('%d-01-01', $currentYear));
        }

        $mainPeriods = self::getMainPeriods($currentYear);
        $years = ($user !== null) ? $this->getYears($user) : [];

        if (in_array($period, array_keys($mainPeriods))) {
            $startDate = $mainPeriods[$period];
        } elseif ($user !== null && strlen((string) $period) === 4 && $period < $currentYear - 1 && in_array($period, $years)) {
            $startDate = new \DateTime(sprintf('%d-01-01', $period));
            $endDate = new \DateTime(sprintf('%d-01-01', $period + 1));
        } else {
            $period = self::YEAR_TO_DATE;
            $startDate = $mainPeriods[$period];
        }

        return new PeriodDatesResult($period, $startDate, $endDate, !empty($years) ? min($years) : $currentYear);
    }

    /**
     * Get an array of available periods for the dropdown list.
     */
    public function getAvailablePeriods(Usr $user): array
    {
        $years = $this->getYears($user);

        $mainPeriods = [
            self::YEAR_TO_DATE => $this->translator->trans(/** @Desc("Year to date") */ 'trips.year-to-date', [], 'trips'),
            self::LAST_YEAR => $this->translator->trans(/** @Desc("Last year") */ 'trips.last-year', [], 'trips'),
            self::LAST_3_YEARS => $this->translator->trans(/** @Desc("Last %y% years") */ 'trips.last-years', ['%y%' => 3, '%count%' => 3], 'trips'),
            self::LAST_5_YEARS => $this->translator->trans(/** @Desc("Last %y% years") */ 'trips.last-years', ['%y%' => 5, '%count%' => 5], 'trips'),
            self::LAST_10_YEARS => $this->translator->trans(/** @Desc("Last %y% years") */ 'trips.last-years', ['%y%' => 10, '%count%' => 10], 'trips'),
        ];

        if (!empty($years)) {
            $mainPeriods[self::ALL_TIME] = $this->translator->trans(/** @Desc("All time (up to %year%)") */ 'trips.all-time', ['%year%' => min($years)], 'trips');

            $currentYear = (int) $this->clock->current()->getAsDateTime()->format('Y');
            $additionalPeriods = array_filter($years, function ($item) use ($currentYear) {
                // Exclude the current and previous years
                return $item < $currentYear - 1;
            });
        }

        return isset($additionalPeriods) ?
            $mainPeriods + array_combine($additionalPeriods, $additionalPeriods) :
            $mainPeriods;
    }

    private static function getMainPeriods(int $currentYear): array
    {
        return [
            self::YEAR_TO_DATE => new \DateTime(sprintf('%d-01-01', $currentYear)),
            self::LAST_YEAR => new \DateTime(sprintf('%d-01-01', $currentYear - 1)),
            self::LAST_3_YEARS => new \DateTime(sprintf('%d-01-01', $currentYear - 3)),
            self::LAST_5_YEARS => new \DateTime(sprintf('%d-01-01', $currentYear - 5)),
            self::LAST_10_YEARS => new \DateTime(sprintf('%d-01-01', $currentYear - 10)),
            self::ALL_TIME => new \DateTime(sprintf('%d-01-01', 2004)),
        ];
    }
}

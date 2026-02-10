<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Formatter;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Error\SafeExecutorFactory;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Scanner\UserMailboxCounter;
use AwardWallet\MainBundle\Service\MileValue\LongHaulDetector;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Airport;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Item;
use AwardWallet\MainBundle\Service\TravelSummary\Data\LocationStat;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Route as ServiceRoute;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Segment;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Summary;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Totals;
use AwardWallet\MainBundle\Service\TravelSummary\PeriodDatesHelper;
use AwardWallet\MainBundle\Service\TravelSummary\Statistics\Distance;
use AwardWallet\MainBundle\Service\TravelSummary\Statistics\Travel;
use AwardWallet\MainBundle\Service\TravelSummary\TravelSummaryService;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MobileFormatter
{
    private LocalizeService $localizer;
    private TravelSummaryService $travelSummary;
    private UserMailboxCounter $mailboxCounter;
    private SafeExecutorFactory $safeExecutorFactory;
    private LongHaulDetector $longHaulDetector;
    private PeriodDatesHelper $periodDatesHelper;

    public function __construct(
        LocalizeService $localizer,
        TravelSummaryService $travelSummary,
        UserMailboxCounter $mailboxCounter,
        SafeExecutorFactory $safeExecutorFactory,
        LongHaulDetector $longHaulDetector,
        PeriodDatesHelper $periodDatesHelper
    ) {
        $this->localizer = $localizer;
        $this->travelSummary = $travelSummary;
        $this->mailboxCounter = $mailboxCounter;
        $this->safeExecutorFactory = $safeExecutorFactory;
        $this->longHaulDetector = $longHaulDetector;
        $this->periodDatesHelper = $periodDatesHelper;
    }

    public function format(Usr $user, ?Useragent $userAgent, int $period): array
    {
        $datesResult = $this->periodDatesHelper->getDates($period);

        return $this->formatBySummary(
            $this->travelSummary->buildPeriodSummary($user, $userAgent, $datesResult->getStartDate(), $datesResult->getEndDate(), true),
            $user,
            $userAgent,
            $datesResult->getCurrentPeriod()
        );
    }

    public function formatBySummary(Summary $summary, Usr $user, ?Useragent $userAgent, int $period)
    {
        $result = \array_merge(
            $summary->jsonSerialize(),
            [
                'currentPeriod' => $period,
                'currentUser' => $userAgent ? $userAgent->getUseragentid() : null,
                'isAwPlus' => $user->isAwPlus(),
                'prevDiff' => null,
            ],
            $this->safeExecutorFactory
                ->make(function () use ($user, $userAgent) {
                    return ['linkMailbox' => ($this->mailboxCounter->myOrFamilyMember($user->getId(), $userAgent ? $userAgent->getId() : null) === 0)];
                })
                ->orValue([])
                ->run()
        );

        // only current and previous year
        if (in_array($period, [PeriodDatesHelper::YEAR_TO_DATE, PeriodDatesHelper::LAST_YEAR])) {
            $result['prevDiff'] = $this->buildPrevPeriodDiff($user, $userAgent, $period, $result);
        }

        return $this->doFormat($result);
    }

    public function getStubData(Usr $user, ?Useragent $userAgent, int $period): array
    {
        $summary = new Summary(
            true,
            [],
            [],
            [],
            [],
            new Totals(0, 0, 0),
            new Travel([], [], [], [], [], [], $this->longHaulDetector),
            new LocationStat(0, 0, 0),
            [],
            new Distance([], [], []),
            0
        );

        return $this->formatBySummary($summary, $user, $userAgent, $period);
    }

    protected function formatAxis(float $value): string
    {
        return \sprintf('%0.4f', \round($value, 4));
    }

    private function buildPrevPeriodDiff(Usr $user, ?Useragent $userAgent = null, int $period, $periodSummary): array
    {
        $currentYear = (int) date('Y');
        $startDate = new \DateTime(sprintf('%d-01-01', $currentYear - $period));
        $endDate = new \DateTime(sprintf('%d-01-01', $currentYear - ($period - 1)));
        $prevPeriodSummary = $this->travelSummary->buildPeriodSummary($user, $userAgent, $startDate, $endDate, true)->jsonSerialize();

        $compareKeyValues = function (string $key) use ($periodSummary, $prevPeriodSummary) {
            $keys = explode('.', $key);

            if (count($keys) > 1) {
                return $this->getValueFromSummary($key, $periodSummary) - $this->getValueFromSummary($key, $prevPeriodSummary);
            }

            return $periodSummary[$key] - $prevPeriodSummary[$key];
        };

        $result = [
            'totalFlights' => $compareKeyValues('travelStatistics.flightStats.totalFlights'),
            'longHaulFlights' => $compareKeyValues('travelStatistics.flightStats.longHaulFlights'),
            'shortHaulFlights' => $compareKeyValues('travelStatistics.flightStats.shortHaulFlights'),
            'countries' => $compareKeyValues('locationStat.countries'),
            'cities' => $compareKeyValues('locationStat.cities'),
            'continents' => $compareKeyValues('locationStat.continents'),
            'distance' => $compareKeyValues('distance.data.distance'),
            'aroundTheWorld' => $compareKeyValues('distance.data.aroundTheWorld'),
        ];

        return $result;
    }

    /**
     * Get the value of the property from `Summary`.
     *
     * @param string $path path to the object property as a dot notation
     * @param array $periodSummary `Data\Summary` object converted to an array
     */
    private function getValueFromSummary($path, $periodSummary)
    {
        $properties = explode('.', $path);

        return (int) array_reduce($properties, function ($carry, $item) {
            $method = 'get' . ucfirst($item);

            return is_array($carry) ? $carry[$item] : $carry->{$method}();
        }, $periodSummary);
    }

    private function doFormat(array $result)
    {
        $flightStats = $result['travelStatistics']->getFlightStats();
        $result['flightStat'] = [
            'totalFlights' => $flightStats->getTotalFlights(),
            'longHaulFlights' => $flightStats->getLongHaulFlights(),
            'shortHaulFlights' => $flightStats->getShortHaulFlights(),
            'longHaulPercentage' => $flightStats->getLongHaulPercentage(),
            'shortHaulPercentage' => $flightStats->getShortHaulPercentage(),
        ];

        $distanceResult = $result['distance']->getData();
        $result['distance'] = $this->localizer->formatNumber($distanceResult->getDistance());
        $result['aroundTheWorld'] = $this->localizer->formatNumber($distanceResult->getAroundTheWorld());

        if (isset($result['prevDiff'])) {
            $result['prevDiff']['distanceFormatted'] = isset($result['prevDiff']['distance']) ? $this->localizer->formatNumber($result['prevDiff']['distance']) : null;
            $result['prevDiff']['aroundTheWorldFormatted'] = isset($result['prevDiff']['aroundTheWorld']) ? $this->localizer->formatNumber($result['prevDiff']['aroundTheWorld']) : null;
        }

        $airportsIndexed =
            it($result['airports'])
            ->reindex(function (Item $item) {
                return $item->getKey();
            })
            ->toArrayWithKeys();

        $result['airports'] =
            it($result['airports'])
            ->map(function (Item $item) use ($airportsIndexed): array {
                $item = $item->jsonSerialize();
                $item['value'] = $this->localizer->formatNumber((int) $item['value']);
                /** @var Airport $payload */
                $payload = $item['payload'];
                $airportCode = $payload->getCode();
                $item['payload'] = [
                    'code' => $payload->getCode(),
                    'latitude' => $payload->getPoint()->getLat(),
                    'longitude' => $payload->getPoint()->getLng(),
                    'airportTitle' => $payload->getTitle(),
                    'segments' => it($payload->getSegments())
                        ->map(function (Segment $segment): array {
                            $depDate = $segment->getDepDate();

                            return [
                                'depDate' => [
                                    'd' => $this->localizer->patternDateTime($depDate, 'd'),
                                    'm' => \trim(\mb_strtoupper($this->localizer->patternDateTime($depDate, 'LLL')), '.'),
                                    'y' => $this->localizer->patternDateTime($depDate, 'y'),
                                ],
                                'depCode' => $segment->getDepCode(),
                                'arrCode' => $segment->getArrCode(),
                                'travelPlan' => $segment->getTravelPlan(),
                                'timelineId' => 'TS.' . $segment->getTripSegmentId(),
                            ];
                        })
                        ->toArray(),
                    'points' => it($payload->getSegments())
                            ->reindex(function (Segment $segment) use ($airportCode) {
                                return $segment->getArrCode() === $airportCode ?
                                    $segment->getDepCode() :
                                    $segment->getArrCode();
                            })
                            ->mapIndexed(function (Segment $segment, string $code) use ($airportsIndexed) {
                                /** @var Airport $indexedPayload */
                                $indexedPayload = $airportsIndexed[$code]->getPayload();
                                $point = $indexedPayload->getPoint();

                                return [
                                    'latitude' => $point->getLat(),
                                    'longitude' => $point->getLng(),
                                ];
                            })
                            ->collectWithKeys()
                            ->values()
                            ->toArray(),
                ];

                return $item;
            })
            ->toArray();

        $result['routes'] =
            it($result['routes'])
            ->flatMapIndexed(function (ServiceRoute $route) {
                $dep = $route->getDep();
                $arr = $route->getArr();
                $key =
                    $this->formatAxis($dep->getLat()) . '-' .
                    $this->formatAxis($dep->getLng()) . '-' .
                    $this->formatAxis($arr->getLat()) . '-' .
                    $this->formatAxis($arr->getLng()) . '-';

                yield $key => [
                    'arr' => [
                        'latitude' => $arr->getLat(),
                        'longitude' => $arr->getLng(),
                    ],
                    'dep' => [
                        'latitude' => $dep->getLat(),
                        'longitude' => $dep->getLng(),
                    ],
                ];
            })
            ->collectWithKeys()
            ->values()
            ->toArray();

        foreach (['airlines', 'countries'] as $listName) {
            $result[$listName] =
                it($result[$listName])
                ->map(function (Item $item) {
                    $item = $item->jsonSerialize();
                    $item['value'] = (int) $item['value'];

                    return $item;
                })
                ->toArray();
        }

        unset($result['travelStatistics']);

        return $result;
    }
}

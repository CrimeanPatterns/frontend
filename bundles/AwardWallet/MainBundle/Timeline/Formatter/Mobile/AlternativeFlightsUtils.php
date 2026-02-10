<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Globals\Features\FeaturesBitSet;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Date as OldDate;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Menu\AlternativeFlight;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Menu\AlternativeFlights;
use AwardWallet\MainBundle\Timeline\Item\AirTrip;
use AwardWallet\MobileBundle\View\DateFormatted;

class AlternativeFlightsUtils
{
    /**
     * @var LocalizeService
     */
    private $localize;

    /**
     * AlternativeFlightsUtils constructor.
     */
    public function __construct(
        LocalizeService $localize
    ) {
        $this->localize = $localize;
    }

    /**
     * @param AirTrip[] $items
     */
    public function schedule(array $items, FeaturesBitSet $formatOptions)
    {
        $tripsByLocatorMap = [];

        foreach ($items as $item) {
            if (StringHandler::isEmpty($item->getConfNo())) {
                $tripsByLocatorMap[] = [$item];
            } else {
                $tripsByLocatorMap[$item->getConfNo()][] = $item;
            }
        }

        /** @var AirTrip[] $trips */
        foreach ($tripsByLocatorMap as $trips) {
            $tripsCount = count($trips);

            $tripCodes = array_map(function (AirTrip $segment) {
                return [
                    $segment->getSource()->getDepcode(),
                    $segment->getSource()->getArrCode(),
                ];
            }, $trips);

            $rounds = self::getCycles($tripCodes);

            foreach ($trips as $tripIndex => $trip) {
                // shrink available rounds
                if ($rounds) {
                    $firstRound = $rounds[0];

                    if ($tripIndex >= $firstRound[0][1]) {
                        $rounds = array_slice($rounds, 1);
                    }
                }

                $tripAlternatives = new AlternativeFlights();

                $dep = $trip->getSource()->getDepcode();
                $arr = $trip->getSource()->getArrcode();
                $startDate = $trip->getStartDate();

                // add current
                $this->addFlight(
                    $tripAlternatives,
                    [$dep, $arr],
                    [$trip->getStartDate()],
                    $formatOptions
                );

                if ($rounds) {
                    // check current trip for inclusion in round-trips
                    foreach ($rounds as $round) {
                        [$roundInterval, $roundTargets] = $round;

                        // skip all intervals in future
                        if ($tripIndex < $roundInterval[0]) {
                            continue;
                        }

                        // last point in round-trip
                        $lastRoundArr = $trips[$roundInterval[1]]->getSource()->getArrcode();

                        // add round-trip alternatives by (current -> [moving target]-> end) pattern
                        foreach ($roundTargets as $roundTarget) {
                            // skip targets in past
                            if ($roundTarget <= $tripIndex) {
                                continue;
                            }

                            $this->addFlight(
                                $tripAlternatives,
                                [$dep, $trips[$roundTarget]->getSource()->getDepcode(), $lastRoundArr],
                                [$startDate, $trips[$roundTarget]->getStartDate()],
                                $formatOptions
                            );
                        }

                        // add flights from current to last segment in round-trip
                        if ($tripIndex < $roundInterval[1]) {
                            // direct flight to last segment
                            $this->addFlight(
                                $tripAlternatives,
                                [$dep, $lastRoundArr],
                                [$startDate],
                                $formatOptions
                            );

                            // transfer flight to last segment
                            $this->addFlight(
                                $tripAlternatives,
                                [$dep, $arr, $lastRoundArr],
                                [$startDate, $trips[$tripIndex + 1]->getStartDate()],
                                $formatOptions
                            );
                        }
                    }
                }

                if ($tripIndex < $tripsCount - 1) {
                    // add flight from current to last segment
                    $this->addFlight(
                        $tripAlternatives,
                        [$dep, end($trips)->getSource()->getArrcode()],
                        [$startDate],
                        $formatOptions
                    );
                }

                unset($tripAlternatives->map);
                $trip->setTripAlternatives($tripAlternatives);
            }
        }
    }

    /**
     * Find round-trips intervals, find round-trip "target" segments.
     *
     * @param array[] $tripCodes dep\arr code pairs
     * @return array[] - typle of array indexes ("first and last indexes", "middle\target indexes")
     */
    public function getCycles(array $tripCodes): array
    {
        /** @var array<point_code, int[]> $openSegmentSeriesMap */
        $openSegmentSeriesMap = [];
        $cycles = [];
        $prevArr = null;

        foreach ($tripCodes as $i => [$dep, $arr]) {
            if ($dep === $arr) {
                continue;
            }

            foreach ($openSegmentSeriesMap as &$points) {
                $points[] = $i;
            }
            unset($points);

            if (isset($prevArr) && $prevArr !== $dep) {
                $prevArr = null;
                $openSegmentSeriesMap = [];

                continue;
            }

            if (!isset($openSegmentSeriesMap[$dep])) {
                $openSegmentSeriesMap[$dep][] = $i;
            }

            if (isset($openSegmentSeriesMap[$arr])) {
                $cycleTrips = $openSegmentSeriesMap[$arr];
                $cycle = [&$cycleTrips];

                $cycleTripsCount = count($cycleTrips);

                // select items from midlle as targets
                switch ($cycleTripsCount) {
                    case 2: $cycle[] = [$cycleTrips[1]];

                        break;

                    case 3: $cycle[] = [$cycleTrips[1], $cycleTrips[2]];

                        break;

                    default:
                        $mid = (int) ($cycleTripsCount / 2);
                        $cycle[] = [
                            $cycleTrips[$mid - 1],
                            $cycleTrips[$mid],
                            $cycleTrips[$mid + 1],
                        ];
                }

                $cycleTrips = [$cycleTrips[0], end($cycleTrips)];

                $cycles[] = $cycle;

                unset($openSegmentSeriesMap[$arr], $cycleTrips);
            }

            $prevArr = $arr;
        }

        return $cycles;
    }

    /**
     * @param string[] $points
     * @param \DateTimeInterface[] $dates
     */
    protected function addFlight(AlternativeFlights $alternativeFlights, array $points, array $dates, FeaturesBitSet $formatOptions)
    {
        if (
            ($points[0] === $points[1])
            || ((count($points) === 3) && ($points[1] === $points[2]))
        ) {
            return;
        }

        $hash = implode('_', $points);

        foreach ($dates as $date) {
            $hash .= '_' . $date->getTimestamp();
        }

        if (isset($alternativeFlights->map[$hash])) {
            return;
        } else {
            $alternativeFlights->map[$hash] = true;
        }

        $alternativeFlight = $this->createAlternativeFlight($points, $dates, $formatOptions);

        if (count($alternativeFlights->main ?? []) >= AlternativeFlights::MAIN_LIMIT) {
            $alternativeFlights->extra[] = $alternativeFlight;
        } else {
            $alternativeFlights->main[] = $alternativeFlight;
        }
    }

    /**
     * @param string[] $points
     * @param \DateTime[] $dates
     */
    protected function createAlternativeFlight(array $points, array $dates, FeaturesBitSet $formatOptions): AlternativeFlight
    {
        $roundTrip = count($points) == 3;

        if ($roundTrip && !count($dates) == 2) {
            throw new \InvalidArgumentException('Invalid time interval for round trip');
        }

        $alternativeFlight = new AlternativeFlight();
        $alternativeFlight->points = $points;

        $tripDates = [];

        foreach ([-1, 0, +1] as $dayOffset) {
            $timeOffset = $dayOffset * SECONDS_PER_DAY;

            if ($roundTrip) {
                /**
                 * @var \DateTime $start
                 * @var \DateTime $end
                 */
                [$start, $end] = $dates;
                $start = clone $start;
                $end = clone $end;

                $tripDates[] = [
                    $this->prepareDate($start->setTimestamp($start->getTimestamp() + $timeOffset), $formatOptions),
                    $this->prepareDate($end->setTimestamp($end->getTimestamp() + $timeOffset), $formatOptions),
                ];
            } else {
                $start = clone $dates[0];
                $tripDates[] = $this->prepareDate($start->setTimestamp($start->getTimestamp() + $timeOffset), $formatOptions);
            }
        }

        $alternativeFlight->dates = $tripDates;

        return $alternativeFlight;
    }

    /**
     * @return DateFormatted|OldDate
     */
    protected function prepareDate(\DateTime $dateTime, FeaturesBitSet $formatOptions)
    {
        return $formatOptions->supports(FormatHandler::REGIONAL_SETTINGS) ?
            new DateFormatted(
                $dateTime->getTimestamp(),
                $this->localize->formatDateTime($dateTime, 'short', null)
            ) :
            new OldDate($dateTime);
    }
}

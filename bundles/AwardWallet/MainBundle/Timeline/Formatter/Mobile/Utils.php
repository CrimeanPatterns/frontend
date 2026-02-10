<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Entity;
use AwardWallet\MainBundle\Globals\Geo;
use AwardWallet\MainBundle\Timeline;

class Utils
{
    public const SPOT_HERO_CITIES = [
        'NYC' => [
            'geo' => [40.7127, -74.0059],
            'air' => ['LGA', 'JFK'],
        ],
        'Newark' => [
            'geo' => [40.735657, -74.1723667],
            'air' => ['EWR'],
        ],
        'Chicago' => [
            'geo' => [41.836944, -87.684722],
            'air' => ['ORD', 'MDW', 'RFD', 'GYY'],
        ],
        'Baltimore' => [
            'geo' => [39.283333, -76.616667],
            'air' => ['BWI', 'MTN'],
        ],
        'Philadelphia' => [
            'geo' => [39.95, -75.166667],
            'air' => ['PHL', 'PNE'],
        ],
        'Denver' => [
            'geo' => [39.76185, -104.881105],
            'air' => ['DEN'],
        ],
        'Boston' => [
            'geo' => [42.358056, -71.063611],
            'air' => ['BOS'],
        ],
        'Milwaukee' => [
            'geo' => [43.052222, -87.955833],
            'air' => ['MKE'],
        ],
        'Minneapolis' => [
            'geo' => [44.983333, -93.266667],
            'air' => ['MSP'],
        ],
        'New Orleans' => [
            'geo' => [29.95, -90.066667],
            'air' => ['MSY'],
        ],
        'San Francisco' => [
            'geo' => [37.783333, -122.416667],
            'air' => ['OAK', 'SFO'],
        ],
        'Washington, D.C.' => [
            'geo' => [38.904722, -77.016389],
            'air' => ['DCA', 'IAD'],
        ],
    ];

    public static function formatDateInterval(\DateInterval $interval)
    {
        return [
            //            'y' => (int) $interval->format('%Y'),
            //            'm' => (int) (($m = $interval->format('%m')) > 0 ? $m - 1 : $m),
            //            'd' => (int) $interval->format('%d'),
            'h' => (int) $interval->format('%H'),
            'i' => (int) $interval->format('%i'),
        ];
    }

    public static function getReservationName(Entity\Reservation $reservation)
    {
        $name = $reservation->getHotelname();

        if (null === $name || '' === $name) {
            if (null !== $reservation->getProvider()) {
                $name = $reservation->getProvider()->getShortname();
            }

            if (null === $name) {
                $name = $reservation->getProvidername();
            }
        }

        return $name;
    }

    public static function getRestaurantName(Entity\Restaurant $restaurant)
    {
        $name = $restaurant->getName();

        if ((null === $name || '' === $name) && null !== $restaurant->getProvider()) {
            $name = $restaurant->getProvider()->getShortname();
        }

        return $name;
    }

    public static function transParams(array $params)
    {
        return array_merge(
            [
                '<gray>' => ' ',
                '</gray>' => '',
            ],
            $params
        );
    }

    /**
     * @param string $propertyName DepName, ArrName, PickupDatetime etc
     * @return Formatted\Components\Date|null
     */
    public static function getChangedDate(Timeline\Diff\Changes $changes, $propertyName)
    {
        $dateTime = self::getChangedDateTime($changes, $propertyName);

        if (null === $dateTime) {
            return null;
        }

        return new Timeline\Formatter\Mobile\Formatted\Components\Date($dateTime);
    }

    /**
     * @param string $propertyName
     * @return \DateTime|null
     */
    public static function getChangedDateTime(Timeline\Diff\Changes $changes, $propertyName)
    {
        $timestamp = $changes->getPreviousValue($propertyName);

        if (null === $timestamp) {
            return null;
        }

        return (new \DateTime())->setTimestamp($timestamp);
    }

    /**
     * @param string $startShift
     * @param string $endShift
     * @return string|null
     */
    public static function parkingUrl(?\AwardWallet\Common\Entity\Geotag $geotag = null, ?\DateTime $baseDate = null, $startShift = null, $endShift = '+5 hours', $airCode = null)
    {
        return null;

        if (
            !$geotag
            || !$baseDate
            || null === ($lat = $geotag->getLat())
            || null === ($long = $geotag->getLng())
        ) {
            return null;
        }

        $found = false;

        if (null !== $airCode) {
            foreach (self::SPOT_HERO_CITIES as $cityData) {
                foreach ($cityData['air'] as $airport) {
                    if ($airport === $airCode) {
                        $found = true;
                        [$cLat, $cLong] = $cityData['geo'];

                        break 2;
                    }
                }
            }
        }

        if (!$found) {
            foreach (self::SPOT_HERO_CITIES as $cityData) {
                [$cLat, $cLong] = $cityData['geo'];

                if (Geo::distance($cLat, $cLong, $lat, $long) <= 30) {
                    if (null === $airCode) {
                        $cLat = $lat;
                        $cLong = $long;
                    }

                    $found = true;

                    break;
                }
            }
        }

        if (!$found) {
            return null;
        }

        $startDate = clone $baseDate;
        $startDate->modify($startShift);

        $endDate = clone $startDate;
        $endDate->modify($endShift);

        $params = [
            'latitude' => number_format($cLat, 6),
            'longitude' => number_format($cLong, 6),

            'start_date' => $startDate->format('m-d-Y'),
            'start_time' => $startDate->format('hiA'),

            'end_date' => $endDate->format('m-d-Y'),
            'end_time' => $endDate->format('hiA'),

            'sha_affiliate' => 'awardwallet',
        ];

        return 'https://spothero.com/search/?' . http_build_query($params);
    }
}

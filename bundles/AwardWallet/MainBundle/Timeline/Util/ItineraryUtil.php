<?php

namespace AwardWallet\MainBundle\Timeline\Util;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\DateRangeInterface;
use AwardWallet\MainBundle\Entity\GeotagInterface;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use Doctrine\Common\Collections\Collection;

class ItineraryUtil
{
    public const MATCH_DISTANCE = 200;

    /**
     * @param GeotagInterface[]|DateRangeInterface[] $segments
     * @param int $matchDistance miles
     * @return Geotag[]
     */
    public static function findTarget(array $segments, $matchDistance = self::MATCH_DISTANCE)
    {
        $tags = self::getGeoTags($segments);
        $distance = self::calcDistance($tags);
        $result = [];

        if ($distance > 0) {
            $roundTrip =
                count($tags) > 2
                && $distance > 0
                && $tags[0]->distanceFrom($tags[count($tags) - 1]) < $matchDistance;

            if ($roundTrip) {
                // extract TripSegments
                $tripsegments = [];

                foreach ($segments as $segment) {
                    if ($segment instanceof Tripsegment) {
                        $tripsegments[] = $segment;
                    } elseif ($segment instanceof Trip) {
                        $tripsegments = array_merge(
                            $tripsegments,
                            (($segmentsCollection = $segment->getSegments()) && $segmentsCollection instanceof Collection) ?
                                $segmentsCollection->toArray() :
                                (array) $segmentsCollection
                        );
                    }
                }

                $point = self::getRoundtripTarget($tripsegments);

                if ($point) {
                    $result = [$point];
                }
            } else {
                $result = [end($tags)];
            }
        } else {
            foreach ($segments as $segment) {
                foreach ($segment->getGeoTags() as $point) {
                    $pointName = $point->getCity();

                    if (isset($pointName) && !in_array($pointName, $result)) {
                        $result[] = $point;
                    }

                    if (count($result) == 3) {
                        break;
                    }
                }

                if (count($result) == 3) {
                    break;
                }
            }
        }

        return $result;
    }

    public static function isOverseasTravel(array $geoTags, bool $isOverseasTrip = false): bool
    {
        if (count($geoTags) < 2) {
            return false;
        }

        if ($isOverseasTrip) {
            if (Country::US_CODE === end($geoTags)->getCountryCode()) {
                return false;
            }

            return true;
        }

        $isFound = false;
        $geoTags = array_values($geoTags);

        /** @var Geotag $item */
        foreach ($geoTags as $item) {
            if (Country::US_CODE === $item->getCountryCode()) {
                $nextTag = next($geoTags);

                if ($nextTag && Country::US_CODE !== $nextTag->getCountryCode()) {
                    $isFound = true;

                    break;
                }
            }
        }

        return $isFound;
    }

    /**
     * @param Geotag[] $tags
     * @return float
     */
    private static function calcDistance(array $tags)
    {
        $result = 0;

        foreach ($tags as $tag) {
            if (!empty($last)) {
                $distance = $tag->distanceFrom($last);

                if ($distance == PHP_INT_MAX) {
                    $result = PHP_INT_MAX;
                } else {
                    $result += $distance;
                }
            }

            $last = $tag;
        }

        return $result;
    }

    /**
     * @param GeotagInterface[]|DateRangeInterface[] $segments
     * @return Geotag[]
     */
    private static function getGeoTags(array $segments)
    {
        $result = [];

        foreach ($segments as $segment) {
            $result = array_merge($result, $segment->getGeoTags());
        }

        return $result;
    }

    /**
     * @param Tripsegment[] $segments
     * @return Geotag|null
     */
    private static function getRoundtripTarget(array $segments)
    {
        $targetPoint = null;

        /** @var Tripsegment $lastTrip */
        foreach ($segments as $segment) {
            $dep = $segment->getDepgeotagid();
            $arr = $segment->getArrgeotagid();

            if (empty($dep) || empty($arr)) {
                continue;
            }

            $distance = $dep->distanceFrom($arr);

            if ($distance > 0) {
                if (isset($prevPoint)) {
                    // TODO: memoize results
                    $pause = $segment->getUTCStartDate()->getTimestamp() - $lastTrip->getUTCEndDate()->getTimestamp();

                    if (!isset($maxPause) || $pause > $maxPause) {
                        $maxPause = $pause;
                        $targetPoint = $prevPoint;
                    }
                }

                $lastTrip = $segment;
                $prevPoint = $arr;
            }
        }

        return $targetPoint;
    }
}

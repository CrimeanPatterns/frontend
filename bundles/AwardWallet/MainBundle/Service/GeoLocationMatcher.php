<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\Common\Geo\GoogleGeo;
use AwardWallet\MainBundle\Globals\Geo;
use Psr\Log\LoggerInterface;

class GeoLocationMatcher
{
    private GoogleGeo $geoCoder;

    private LoggerInterface $logger;

    public function __construct(GoogleGeo $geoCoder, LoggerInterface $logger)
    {
        $this->geoCoder = $geoCoder;
        $this->logger = $logger;
    }

    /**
     * @param string|Geotag $location1
     * @param string|Geotag $location2
     */
    public function match($location1, $location2, float $maxDistance = 0.1, bool $logs = true): bool
    {
        if (is_null($location1) || is_null($location2)) {
            if ($logs) {
                $this->logger->info('GeoLocationMatcher: one of the locations is null');
            }

            return false;
        }

        if (is_string($location1)) {
            $geoTag1 = $this->geoCoder->findGeoTagEntity($location1);
        } else {
            $geoTag1 = $location1;
            $location1 = $geoTag1->getAddress();
        }

        if (is_string($location2)) {
            $geoTag2 = $this->geoCoder->findGeoTagEntity($location2);
        } else {
            $geoTag2 = $location2;
            $location2 = $geoTag2->getAddress();
        }

        // If the locations are the same, we don't need to check the distance
        if (!is_null($location1) && !is_null($location2) && strcasecmp($location1, $location2) === 0) {
            if ($logs) {
                $this->logger->info('GeoLocationMatcher: matched by name');
            }

            return true;
        }

        if (
            is_null($geoTag1)
            || is_null($geoTag2)
            || is_null($geoTag1->getLat())
            || is_null($geoTag1->getLng())
            || is_null($geoTag2->getLat())
            || is_null($geoTag2->getLng())
        ) {
            if ($logs) {
                $this->logger->info('GeoLocationMatcher: one of the locations is missing geo data');
            }

            return false;
        }

        if (
            (
                $distance = Geo::distance(
                    $geoTag1->getLat(),
                    $geoTag1->getLng(),
                    $geoTag2->getLat(),
                    $geoTag2->getLng()
                )
            ) > $maxDistance
        ) {
            if ($logs) {
                $this->logger->info(sprintf('GeoLocationMatcher: distance is too big: %s (max: %s)', $distance, $maxDistance));
            }

            return false;
        }

        return true;
    }
}

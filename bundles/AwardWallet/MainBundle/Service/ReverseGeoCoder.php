<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\Common\Geo\GeoCodingFailedException;
use AwardWallet\Common\Geo\Google;
use AwardWallet\Common\Geo\Google\LatLng;

class ReverseGeoCoder
{
    /**
     * @var Google\GoogleApi
     */
    private $googleApi;

    public function __construct(Google\GoogleApi $googleApi)
    {
        $this->googleApi = $googleApi;
    }

    /**
     * Query for geotag by coordinates.
     *
     * @return Geotag[]
     * @throws GeoCodingFailedException
     */
    public function reverseQuery(float $lat, float $lng)
    {
        try {
            $googleGeoResponse = $this->googleApi->reverseGeoCode(Google\ReverseGeoCodeParameters::makeFromLatLng($lat, $lng));
        } catch (Google\GoogleRequestFailedException $e) {
            throw new GeoCodingFailedException("Failed to reverse geocode coordinates: $lat, $lng", $e->getCode(), $e);
        }

        return $this->convertGeoTags($googleGeoResponse->getResults());
    }

    /**
     * @param Google\GeoTag[] $googleGeoTags
     * @return Geotag[]
     */
    private function convertGeoTags(array $googleGeoTags)
    {
        $geoTags = [];
        $addresses = array_map(function (Google\GeoTag $googleGeoTag) {
            return $googleGeoTag->getFormattedAddress();
        }, $googleGeoTags);

        foreach ($googleGeoTags as $googleGeoTag) {
            $geoTags[] = $this->makeGeoTag($googleGeoTag, $googleGeoTag->getFormattedAddress());
        }

        return $geoTags;
    }

    /**
     * @return Geotag
     */
    private function makeGeoTag(Google\GeoTag $googleTag, $query)
    {
        $geoTag = new Geotag();
        $geoTag->setAddress($query);
        $geoTag->setLat($googleTag->getGeometry()->getLocation()->getLat());
        $geoTag->setLng($googleTag->getGeometry()->getLocation()->getLng());
        $geoTag->setUpdatedate(new \DateTime());
        $geoTag->setFoundaddress($googleTag->getFormattedAddress());
        $geoTag->setAddressline($googleTag->getAddressline());
        $geoTag->setCity($googleTag->getCity());
        $geoTag->setState($googleTag->getState());
        $geoTag->setStateCode($googleTag->getStateShort());
        $geoTag->setCountry($googleTag->getCountry());
        $geoTag->setCountryCode($googleTag->getCountryShort());
        $geoTag->setPostalcode($googleTag->getPostalCode());
        $geoTag->setHostname(gethostname());
        $timeZone = $this->getTimeZone($googleTag->getGeometry()->getLocation());

        if (null !== $timeZone) {
            $geoTag->setTimeZoneLocation($timeZone->getName());
        }

        return $geoTag;
    }

    /**
     * @return \DateTimeZone|null
     */
    private function getTimeZone(LatLng $latLng)
    {
        try {
            $googleTimeZone = $this->googleApi->timeZone(Google\TimeZoneParameters::makeFromLatLng($latLng));
        } catch (Google\GoogleRequestFailedException $e) {
            return null;
        }

        return new \DateTimeZone($googleTimeZone->getTimeZoneId());
    }
}

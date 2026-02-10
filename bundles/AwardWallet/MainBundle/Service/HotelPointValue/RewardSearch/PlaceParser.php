<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue\RewardSearch;

use AwardWallet\Common\Geo\Google\GeoCodeParameters;
use AwardWallet\Common\Geo\Google\GeoTag;
use AwardWallet\Common\Geo\Google\GoogleApi;
use AwardWallet\Common\Geo\Google\GoogleRequestFailedException;
use AwardWallet\Common\Geo\Google\PlaceDetails;
use AwardWallet\Common\Geo\Google\PlaceDetailsParameters;
use Psr\Log\LoggerInterface;

class PlaceParser
{
    private LoggerInterface $logger;
    private GoogleApi $googleApi;

    public function __construct(
        LoggerInterface $logger,
        GoogleApi $googleApi
    ) {
        $this->logger = $logger;
        $this->googleApi = $googleApi;
    }

    public function getByPlaceId(string $placeId): ?PlaceParserResult
    {
        $parameters = PlaceDetailsParameters::makeFromPlaceId($placeId);

        try {
            $placeDetails = $this->googleApi->placeDetails($parameters)->getResult();
        } catch (GoogleRequestFailedException $e) {
            $this->logger->info('GoogleRequest failed by "place_id"', ['place_id' => $placeId]);

            return null;
        }

        return $this->parseByPlaceDetails($placeDetails);
    }

    public function getByPlaceName(string $place): ?PlaceParserResult
    {
        $parameters = GeoCodeParameters::makeFromAddress(urldecode($place));
        $geoCodeResponse = $this->googleApi->geoCode($parameters);

        $geoTag = $geoCodeResponse->getResults()[0] ?? null;

        if (null === $geoTag) {
            return null;
        }

        return $this->parseByGeoTag($geoTag);
    }

    private function parseByGeoTag(GeoTag $geoTag): PlaceParserResult
    {
        return new PlaceParserResult(
            $geoTag->getPlaceId(),
            $geoTag->getGeometry()->getLocation()->getLat(),
            $geoTag->getGeometry()->getLocation()->getLng(),
            $geoTag->getCountryShort(),
            $geoTag->getStateShort(),
            $geoTag->getCity(),
            $geoTag->getAddressLine()
        );
    }

    private function parseByPlaceDetails(PlaceDetails $placeDetails): PlaceParserResult
    {
        return new PlaceParserResult(
            $placeDetails->getPlaceId(),
            $placeDetails->getGeometry()->getLocation()->getLat(),
            $placeDetails->getGeometry()->getLocation()->getLng(),
            $placeDetails->getCountryShort(),
            $placeDetails->getStateShort(),
            $placeDetails->getCity(),
            $placeDetails->getAddressLine()
        );
    }
}

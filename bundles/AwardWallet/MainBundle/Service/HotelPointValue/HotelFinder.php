<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\Entity\HotelBrand;
use AwardWallet\MainBundle\Globals\Geo;
use Psr\Log\LoggerInterface;

class HotelFinder
{
    private LoggerInterface $logger;
    private string $tpoToken;
    private string $tpoMarker;
    private \HttpDriverInterface $httpDriver;
    private HotelNameFilter $hotelNameFilter;
    private BrandMatcher $brandMatcher;
    private HotelSourceInterface $hotelSource;

    public function __construct(
        LoggerInterface $logger,
        string $tpoToken,
        string $tpoMarker,
        \HttpDriverInterface $httpDriver,
        HotelNameFilter $hotelNameFilter,
        BrandMatcher $brandMatcher,
        HotelSourceInterface $hotelSource
    ) {
        $this->logger = $logger;
        $this->tpoToken = $tpoToken;
        $this->tpoMarker = $tpoMarker;
        $this->httpDriver = $httpDriver;
        $this->hotelNameFilter = $hotelNameFilter;
        $this->brandMatcher = $brandMatcher;
        $this->hotelSource = $hotelSource;
    }

    public function searchHotel(string $hotelName, float $lat, float $lng, ?HotelBrand $brand): ?HotelFinderResult
    {
        $hotels = $this->hotelSource->searchByLatLng($lat, $lng);

        if (count($hotels) === 0) {
            return null;
        }

        $maxSimilarity = 0;
        $bestMatch = null;
        $hotelName = $this->hotelNameFilter->filter($hotelName);

        foreach ($hotels as $hotel) {
            $distance = round(Geo::distance(
                $lat,
                $lng,
                $hotel->getLat(),
                $hotel->getLng()
            ), 1);

            if ($distance >= 5) {
                $this->logger->debug("skipping {$hotel->getName()}, too distant: {$distance} miles");

                continue;
            }

            if ($brand !== null) {
                $aBrand = $this->brandMatcher->match($hotel->getName(), $brand->getProvider()->getId());

                if ($aBrand === null) {
                    $this->logger->debug("skipping {$hotel->getName()}, no brand matches");

                    continue;
                }

                if ($aBrand->getId() !== $brand->getId()) {
                    $this->logger->debug("skipping {$hotel->getName()}, brand mismatch: {$brand->getName()} <> {$aBrand->getName()}");

                    continue;
                }
            }

            similar_text($hotelName, $this->hotelNameFilter->filter($hotel->getName()), $similarity);
            $similarity = round($similarity, 1);

            if ($similarity < 80) {
                $this->logger->debug("skipping {$hotel->getName()}, similarity is too low: {$similarity}%");

                continue;
            }

            if ($similarity > $maxSimilarity) {
                $this->logger->debug("hotel selected, id: {$hotel->getId()}, {$hotel->getName()}, similarity: {$similarity}");
                $bestMatch = $hotel;
            }
        }

        if ($bestMatch !== null) {
            return $bestMatch;
        }

        $this->logger->debug("failed to find hotel {$hotelName} at {$lat} / {$lng}");

        return null;
    }
}

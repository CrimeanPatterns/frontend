<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use Psr\Log\LoggerInterface;

class PriceFinder
{
    private LoggerInterface $logger;

    private string $tpoToken;

    private string $tpoMarker;

    private \HttpDriverInterface $httpDriver;

    private HotelNameFilter $hotelNameFilter;

    private BrandMatcher $brandMatcher;

    private HotelFinder $hotelFinder;

    public function __construct(
        LoggerInterface $logger,
        string $tpoToken,
        string $tpoMarker,
        \HttpDriverInterface $httpDriver,
        HotelNameFilter $hotelNameFilter,
        BrandMatcher $brandMatcher,
        HotelFinder $hotelFinder
    ) {
        $this->logger = $logger;
        $this->tpoToken = $tpoToken;
        $this->tpoMarker = $tpoMarker;
        $this->httpDriver = $httpDriver;
        $this->hotelNameFilter = $hotelNameFilter;
        $this->brandMatcher = $brandMatcher;
        $this->hotelFinder = $hotelFinder;
    }

    public function search(PointValueParams $params): ?Price
    {
        $hotel = $this->hotelFinder->searchHotel(
            $params->getHotelname(),
            $params->getLat(),
            $params->getLng(),
            $params->getBrand()
        );

        if ($hotel === null) {
            return null;
        }

        return $this->searchByHotelId($params, $hotel->getId());
    }

    public function searchByHotelId(PointValueParams $params, string $hotelId): ?Price
    {
        $prices = $this->searchPrices($params, $hotelId);

        if (count($prices) === 0) {
            return null;
        }

        $match = $this->selectBestMatch($prices);

        if ($match === null) {
            return null;
        }

        $this->logger->info("price found: {$match['price']}, {$match['name']}, {$match['address']}");

        return new Price($match['rooms'][0]['total'], $match['location']['lat'], $match['location']['lon'], $match['fullUrl'], $match['rooms'][0]['fullBookingURL'], $match['name'], $match['address']);
    }

    private function searchPrices(PointValueParams $params, string $hotelId): array
    {
        $searchParams = [
            "adultsCount" => $params->getGuestCount(),
            "checkIn" => $params->getCheckindate()->format("Y-m-d"),
            "checkOut" => $params->getCheckoutdate()->format("Y-m-d"),
            "childrenCount" => $params->getKidsCount(),
            "currency" => "USD",
            "hotelId" => $hotelId,
            "waitForResult" => 1,
        ];

        $searchParams["signature"] = md5("{$this->tpoToken}:{$this->tpoMarker}:" . implode(":", $searchParams));
        $searchParams["marker"] = $this->tpoMarker;

        $response = $this->httpDriver->request(new \HttpDriverRequest("http://engine.hotellook.com/api/v2/search/start.json?" . http_build_query($searchParams)));
        $json = json_decode($response->body, true);

        if (empty($json['result'])) {
            $this->logger->info("prices not found");

            return [];
        }

        return $json['result'];
    }

    private function selectBestMatch(array $matches): ?array
    {
        usort($matches, function (array $a, array $b) {
            return $a['minPriceTotal'] <=> $b['minPriceTotal'];
        });

        /*        if ($roomCount !== null && $roomCount > 1) {
                    $matches = array_filter($matches, function (array $match) use($roomCount) {
                        return count($match['rooms']) >= $roomCount;
                    });
                }

                if (empty($matches)) {
                    $this->output->writeln("could not find matching multi-room ($roomCount) suite");
                    return null;
                }*/

        usort($matches[0]['rooms'], function (array $a, array $b) {
            return $a['total'] <=> $b['total'];
        });

        return $matches[0];
    }
}

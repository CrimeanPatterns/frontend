<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class SingleHotelSource implements HotelSourceInterface
{
    private string $tpoToken;
    private string $tpoMarker;
    private \HttpDriverInterface $httpDriver;

    public function __construct(
        string $tpoToken,
        string $tpoMarker,
        \HttpDriverInterface $httpDriver
    ) {
        $this->tpoToken = $tpoToken;
        $this->tpoMarker = $tpoMarker;
        $this->httpDriver = $httpDriver;
    }

    public function searchByLatLng(float $lat, float $lng): array
    {
        $response = $this->httpDriver->request(new \HttpDriverRequest("http://engine.hotellook.com/api/v2/lookup.json?token={$this->tpoToken}&lookFor=hotel&query=" . urlencode($lat . ',' . $lng)));
        $json = json_decode($response->body, true);

        if (!isset($json['results']['hotels'])) {
            return [];
        }

        return it($json['results']['hotels'])
            ->map(fn (array $hotel) => new HotelFinderResult(
                $hotel['id'],
                $hotel['name'],
                $hotel['location']['lat'],
                $hotel['location']['lon']
            ))
            ->toArray()
        ;
    }
}

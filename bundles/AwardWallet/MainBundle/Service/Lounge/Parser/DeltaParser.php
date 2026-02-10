<?php

namespace AwardWallet\MainBundle\Service\Lounge\Parser;

use AwardWallet\MainBundle\Entity\LoungeSource;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Lounge\CurlBrowser;
use AwardWallet\MainBundle\Service\Lounge\DTO\ValueDTO;
use AwardWallet\MainBundle\Service\Lounge\HttpException;
use AwardWallet\MainBundle\Service\Lounge\LoungeHelper;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\RawOpeningHours;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class DeltaParser implements ParserInterface
{
    public const CODE = 'delta';

    private CurlBrowser $browser;

    private LoungeHelper $loungeHelper;

    public function __construct(CurlBrowser $browser, LoungeHelper $loungeHelper)
    {
        $this->browser = $browser;
        $this->loungeHelper = $loungeHelper;
    }

    public function __toString()
    {
        return $this->getCode();
    }

    public function getCode(): string
    {
        return self::CODE;
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function isParsingFrozen(): bool
    {
        return false;
    }

    public function requestAirports(callable $airportFilter): iterable
    {
        $this->browser->resetProxyList();
        $response = $this->tryGetLocations();

        if (is_null($response) || !isset($response['airportList']) || !is_array($response['airportList'])) {
            throw new HttpException('airportList was not found');
        }

        yield from it($response['airportList'])
            ->reindexByColumn('code')
            ->filter(function (array $airportData) use ($airportFilter) {
                return isset($airportData['lounges']) && $airportFilter($airportData['code']);
            })
            ->map(function (array $airportData) use ($response) {
                return array_map(function (array $lounge) use ($response, $airportData) {
                    return array_merge($lounge, [
                        'amenitiesNames' => it($response['amenities'])->reindexByColumn('serviceCode')->column('description')->toArrayWithKeys(),
                        'url' => 'https://www.delta.com' . ($airportData['mapURL'] ?? '/us/en/delta-sky-club/locations'),
                    ]);
                }, $airportData['lounges']);
            });
    }

    public function getLounge(string $airportCode, $loungeData): ?LoungeSource
    {
        $location = $loungeData['location'];

        if (empty($location)) {
            return null;
        }

        if (preg_match("#(.+)\(?:(?:Temporarily\s+)?Closed\)#", $location, $m)) {
            $available = false;
            $location = trim($m[1]);
        }

        $locationData = preg_replace('/\s+/', ' ', array_map('trim', explode(',', $location)));

        if (count($locationData) > 1) {
            $name = array_shift($locationData);
            $location = implode(', ', preg_replace('/\s+/', ' ', $locationData));
        } else {
            $name = $location;
        }

        // remove "After Security Check"
        $name = trim(preg_replace('/\s+After\s+Security\s+Checks\s*/i', '', $name));

        if (StringHandler::isEmpty($name)) {
            throw new \RuntimeException(sprintf('Delta lounge name is empty: %s', $location));
        }

        $carrierDto = null;

        if (!empty($loungeData['operator']) && !empty($loungeData['operator']['airlineName'])) {
            $operator = $loungeData['operator']['airlineName'];

            if (!preg_match('/\bLounges\b/ims', $operator)) {
                $operator = trim(preg_replace('/\s{2,}/', ' ', str_replace('Lounge', '', $operator)));
                $carrierDto = $this->loungeHelper->parseCarrier(
                    [new ValueDTO($operator), new ValueDTO($name), new ValueDTO('delta')],
                    [new ValueDTO($operator)]
                );
            }
        }

        $locationValues = [new ValueDTO($location)];
        $locationDto = $this->loungeHelper->parseLocation($locationValues, $locationValues);

        $lounge = (new LoungeSource())
            ->setAirportCode($airportCode)
            ->setName($name)
            ->setTerminal($locationDto->getTerminal())
            ->setGate($locationDto->getGate())
            ->setGate2($locationDto->getGate2())
            ->setOpeningHours(
                isset($loungeData['operatingHours']) ? new RawOpeningHours(\json_encode($loungeData['operatingHours'])) : null
            )
            ->setAvailable($available ?? true)
            ->setLocation($location)
            ->setAdditionalInfo(null)
            ->setAmenities(
                implode(
                    ', ',
                    array_filter(array_map(function ($amenity) use ($loungeData) {
                        return $loungeData['amenitiesNames'][$amenity] ?? null;
                    }, array_column($loungeData['amenities'], 'serviceCode')))
                )
            )
            ->setRules(null)
            ->setSourceId(hash('sha256', $airportCode . $name . $location))
            ->setImages([])
            ->setUrl($loungeData['url'] ?? null)
            ->setPriorityPassAccess(null)
            ->setAmexPlatinumAccess(null)
            ->setDragonPassAccess(null)
            ->setLoungeKeyAccess(null)
            ->setAirlines($carrierDto ? $carrierDto->getAirlines() : [])
            ->setAlliances($carrierDto ? $carrierDto->getAlliances() : []);

        return $lounge;
    }

    private function tryGetLocations(int $attempt = 1, int $maxAttempts = 3): ?array
    {
        try {
            return $this->browser->json(
                'https://www.delta.com/skyclub/json/filterLocationsJSON.action',
                'post',
                'filterSearchStr=' . urlencode('{}'),
                [
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Accept' => 'application/json, text/javascript, */*; q=0.01',
                    'Origin' => 'https://www.delta.com',
                    'Referer' => 'https://www.delta.com/us/en/delta-sky-club/locations',
                ]
            );
        } catch (HttpException $e) {
            if ($e->getCode() == 444 && $attempt < $maxAttempts) {
                $delaySeconds = min(2 ** $attempt, 8);
                usleep($delaySeconds * 1_000_000);

                return $this->tryGetLocations($attempt + 1, $maxAttempts);
            }

            throw $e;
        }
    }
}

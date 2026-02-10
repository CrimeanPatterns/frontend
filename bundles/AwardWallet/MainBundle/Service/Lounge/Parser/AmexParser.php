<?php

namespace AwardWallet\MainBundle\Service\Lounge\Parser;

use AwardWallet\MainBundle\Entity\LoungeSource;
use AwardWallet\MainBundle\Service\Lounge\CurlBrowser;
use AwardWallet\MainBundle\Service\Lounge\DTO\ValueDTO;
use AwardWallet\MainBundle\Service\Lounge\HttpException;
use AwardWallet\MainBundle\Service\Lounge\LoungeHelper;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\StructuredOpeningHours;
use AwardWallet\MainBundle\Service\Lounge\OpeningHoursParser;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class AmexParser implements ParserInterface
{
    public const CODE = 'amex';

    private CurlBrowser $curlBrowser;

    private LoungeHelper $loungeHelper;

    private \Memcached $memcached;

    public function __construct(CurlBrowser $curlBrowser, LoungeHelper $loungeHelper, \Memcached $memcached)
    {
        $this->curlBrowser = $curlBrowser;
        $this->loungeHelper = $loungeHelper;
        $this->memcached = $memcached;
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
        $this->curlBrowser->resetProxyList();
        $this->curlBrowser->setDefaultHeaders(['Referer' => 'https://global.americanexpress.com/']);
        $airports = $this->memcached->get('lounge.amex.airports');

        if ($airports === false) {
            $airports = $this->curlBrowser->json('https://global.americanexpress.com/api/lounge_finder/airports?product=the-platinum-card&locale=en-US&lang=en-US');

            if (!is_array($airports) || count($airports) === 0) {
                throw new HttpException('Airports data was not found');
            }

            $this->memcached->set('lounge.amex.airports', $airports, 60 * 60 * 24);
        }

        if (!is_array($airports) || count($airports) === 0) {
            throw new HttpException('Airports data was not found');
        }

        yield from it($airports)
            ->filter(fn (array $airportData) => isset($airportData['iata']) && $airportFilter($airportData['iata']))
            ->reindex(fn (array $airportData) => $airportData['iata'])
            ->onEach(fn () => usleep(rand(300000, 600000)))
            ->map(function (array $airportData) {
                try {
                    $lounges = $this->curlBrowser->json("https://global.americanexpress.com/api/lounge_finder/airports/{$airportData['iata']}/lounges?product=the-platinum-card&locale=en-US&lang=en-US");
                } catch (HttpException $e) {
                    if ($e->getCode() !== 404) {
                        throw $e;
                    }

                    $lounges = [];
                }

                if (!is_array($lounges) || count($lounges) === 0) {
                    return [];
                }

                return $lounges;
            });
    }

    public function getLounge(string $airportCode, $loungeData): ?LoungeSource
    {
        $name = trim(preg_replace('/\(([^\(]+)?\bclosed\b([^\)]*)?\)/ims', '', $loungeData['name'] ?? ''));

        if (empty($name)) {
            throw new \RuntimeException(sprintf('Amex lounge name is empty for airport %s', $airportCode));
        }

        $info = array_merge(
            array_map(fn (string $info) => sprintf('[General] %s', $info), $loungeData['importantInformation']['general'] ?? []),
            array_map(fn (string $info) => sprintf('[Regulatory] %s', $info), $loungeData['importantInformation']['regulatory'] ?? []),
            array_map(fn (string $info) => sprintf('[Warning] %s', $info), $loungeData['importantInformation']['warning'] ?? [])
        );
        $locationDto = $this->loungeHelper->parseLocation(
            [new ValueDTO($loungeData['location']['terminal'] ?? null, false)],
            [new ValueDTO($loungeData['location']['terminal_directions'] ?? null)]
        );
        $carrierDto = $this->loungeHelper->parseCarrier(
            [
                new ValueDTO($loungeData['name'] ?? null),
                new ValueDTO($loungeData['description'] ?? null),
            ],
            []
        );
        $oh = null;

        if (
            isset($loungeData['hours'])
            && is_array($loungeData['hours'])
        ) {
            $oh = $this->parseOpeningHours(
                $loungeData['hours'],
                $loungeData['airport']['time_zone'] ?? 'UTC'
            );
        }

        $lounge = (new LoungeSource())
            ->setAirportCode($airportCode)
            ->setName($name)
            ->setTerminal($locationDto->getTerminal())
            ->setGate($locationDto->getGate())
            ->setGate2($locationDto->getGate2())
            ->setOpeningHours($oh)
            ->setAvailable(preg_match('/\bClosed\b/ims', $loungeData['name']) ? false : true)
            ->setLocation($loungeData['location']['terminal_directions'] ?? null)
            ->setAdditionalInfo($loungeData['description'] ?? null)
            ->setAmenities(
                it($loungeData['amenities'] ?? [])
                    ->column('name')
                    ->joinToString(', ')
            )
            ->setRules(count($info) > 0 ? implode("\n", $info) : null)
            ->setSourceId($loungeData['id'])
            ->setImages(
                it($loungeData['images'] ?? [])
                    ->column('images')
                    ->map(fn (array $image) => $image['medium'] ?? $image['large'] ?? $image['small'] ?? $image['full'] ?? null)
                    ->filterNotNull()
                    ->toArray()
            )
            ->setUrl("https://global.americanexpress.com/lounge-access/the-platinum-card/{$airportCode}/{$loungeData['id']}?locale=en-us")
            ->setPriorityPassAccess(null)
            ->setAmexPlatinumAccess(true)
            ->setDragonPassAccess(null)
            ->setLoungeKeyAccess(null)
            ->setAirlines($carrierDto->getAirlines())
            ->setAlliances($carrierDto->getAlliances());

        return $lounge;
    }

    private function parseOpeningHours(array $hours, string $tz): ?StructuredOpeningHours
    {
        try {
            if (count($hours) === 7) {
                $weekDaysOrder = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                uksort($hours, function (string $a, string $b) use ($weekDaysOrder) {
                    return array_search($a, $weekDaysOrder) <=> array_search($b, $weekDaysOrder);
                });

                return new StructuredOpeningHours($tz, OpeningHoursParser::parse($hours));
            }
        } catch (\InvalidArgumentException $e) {
        }

        return null;
    }
}

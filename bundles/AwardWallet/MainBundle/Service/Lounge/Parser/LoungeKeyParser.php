<?php

namespace AwardWallet\MainBundle\Service\Lounge\Parser;

use AwardWallet\MainBundle\Entity\LoungeSource;
use AwardWallet\MainBundle\Service\Lounge\CurlBrowser;
use AwardWallet\MainBundle\Service\Lounge\DTO\ValueDTO;
use AwardWallet\MainBundle\Service\Lounge\HttpException;
use AwardWallet\MainBundle\Service\Lounge\LoungeHelper;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\RawOpeningHours;
use AwardWallet\MainBundle\Service\Lounge\Storage;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class LoungeKeyParser implements ParserInterface
{
    public const CODE = 'loungeKey';
    private const HEADERS = [
        'X-Requested-With' => 'XMLHttpRequest',
        'Referer' => 'https://airport.mastercard.com/',
        'cookie' => 'mastercard#lang=en',
    ];

    private CurlBrowser $browser;

    private LoungeHelper $loungeHelper;

    private Storage $storage;

    public function __construct(CurlBrowser $browser, LoungeHelper $loungeHelper, Storage $storage)
    {
        $this->browser = $browser;
        $this->loungeHelper = $loungeHelper;
        $this->storage = $storage;
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
        $airportLinks = $this->getAirportLinks();

        yield from it($airportLinks)
            ->filter(function (array $airportLink) use ($airportFilter) {
                return $airportFilter($airportLink['iata']);
            })
            ->onEach(fn () => usleep(rand(300000, 1000000)))
            ->map(function (array $airportLink) {
                foreach ($airportLink['urls'] as $url) {
                    $this->browser->get(
                        sprintf('https://airport.mastercard.com%s', $url),
                        self::HEADERS
                    );

                    $loungeUrls = $this->browser->findXpath("//article//a[contains(@href, 'loungecode=')]/@href");
                    $terminalUrls = $this->browser->findXpath("//*[contains(@class, 'airport-terminals')]//a[contains(@href, '/lounge-finder/terminal')]/@href");

                    if (!empty($terminalUrls)) {
                        foreach ($terminalUrls as $terminalUrl) {
                            $this->browser->get(
                                sprintf('https://airport.mastercard.com%s', $terminalUrl),
                                self::HEADERS
                            );
                            $loungeUrls = array_merge(
                                $loungeUrls,
                                $this->browser->findXpath("//article//a[contains(@href, 'loungecode=')]/@href")
                            );
                        }
                    }

                    $loungeCodes = array_values(array_unique(array_filter(array_map(
                        function (string $loungeUrl) {
                            if (preg_match('#loungecode=([A-Z0-9]+)#ims', $loungeUrl, $matches)) {
                                return $matches[1];
                            }

                            return null;
                        },
                        $loungeUrls
                    ))));

                    return it($loungeCodes)
                        ->map(
                            function (string $loungeCode) use ($airportLink) {
                                try {
                                    $loungeData = $this->browser->json(
                                        sprintf(
                                            'https://www.loungekey.com/api/loungekey/LkSearch/GetLounge?sc_site=mcworld&airportcode=%s&loungecode=%s',
                                            $airportLink['iata'],
                                            $loungeCode
                                        ),
                                        'get',
                                        null,
                                        [
                                            'X-Requested-With' => 'XMLHttpRequest',
                                            'Referer' => 'https://www.loungekey.com',
                                            'cookie' => 'mcworld#lang=en',
                                        ]
                                    );
                                } catch (HttpException $e) {
                                    if (in_array($loungeCode, ['MSY1', 'CJU1', 'GRU19'], true)) {
                                        return null;
                                    }

                                    throw $e;
                                }

                                return array_merge(
                                    $loungeData,
                                    [
                                        'url' => sprintf('https://www.loungekey.com/en/mcworld/lounge-finder/airport?airportcode=%s&loungecode=%s', $airportLink['iata'], $loungeCode),
                                    ]
                                );
                            }
                        )
                        ->filterNotNull()
                        ->toArray();
                }
            });
    }

    public function getLounge(string $airportCode, $loungeData): ?LoungeSource
    {
        if (!isset($loungeData['LoungeName']) || !isset($loungeData['LoungeCode']) || !isset($loungeData['url'])) {
            throw new \RuntimeException('Lounge data is invalid');
        }

        $locationDto = $this->loungeHelper->parseLocation(
            [new ValueDTO($loungeData['TerminalName'] ?? null, false)],
            [new ValueDTO($loungeData['Location'] ?? null)]
        );

        $lounge = (new LoungeSource())
            ->setAirportCode($airportCode)
            ->setName($loungeData['LoungeName'])
            ->setTerminal($locationDto->getTerminal())
            ->setGate($locationDto->getGate())
            ->setGate2($locationDto->getGate2())
            ->setOpeningHours(
                isset($loungeData['OpeningHours']) ? new RawOpeningHours($loungeData['OpeningHours']) : null
            )
            ->setAvailable($loungeData['IsAvailable'] ?? null)
            ->setLocation($loungeData['Location'] ?? null)
            ->setAdditionalInfo(!empty($loungeData['Additional']) ? $loungeData['Additional'] : null)
            ->setAmenities(
                isset($loungeData['Facilities'])
                    ? it($loungeData['Facilities'])->map(fn ($facility) => $facility['FacilityName'])->joinToString(', ')
                    : null
            )
            ->setRules($loungeData['Conditions'] ?? null)
            ->setSourceId($loungeData['LoungeCode'])
            ->setImages(
                $loungeData['Images'] ?? null
            )
            ->setUrl($loungeData['url'])
            ->setPriorityPassAccess(null)
            ->setAmexPlatinumAccess(null)
            ->setDragonPassAccess(null)
            ->setLoungeKeyAccess(true)
            ->setAirlines([])
            ->setAlliances([]);

        return $lounge;
    }

    /**
     * @return array<string, string[]>
     */
    private function getAirportLinks(): array
    {
        $cache = $this->storage->get('loungekey.airportCodes');

        if (is_array($cache)) {
            return $cache;
        }

        $this->browser->get('https://airport.mastercard.com/mastercard/en/sitemap.xml', self::HEADERS);
        $sitemapIndex = @\simplexml_load_string((string) $this->browser->getResponse());

        if (!$sitemapIndex) {
            throw new \RuntimeException('Failed to load sitemap');
        }

        $countryLinks = [];

        foreach ($sitemapIndex->url as $url) {
            $loc = (string) $url->loc;

            if (preg_match('#\/en\/lounge-finder\/country\?countrycode=[A-Z]{2,3}#', $loc, $matches)) {
                $countryLinks[] = sprintf('https://airport.mastercard.com%s', $loc);
            }
        }

        if (\count($countryLinks) < 100) {
            throw new \RuntimeException('Failed to load airport links');
        }

        $airportCodes = [];

        foreach ($countryLinks as $countryLink) {
            $this->browser->get($countryLink, self::HEADERS);
            $airportUrls = $this->browser->findXpath("//dd//a[contains(@href,'/lounge-finder/')]/@href");

            foreach ($airportUrls as $airportUrl) {
                if (preg_match('#airportcode=([A-Z]{3})#', $airportUrl, $matches)) {
                    $airportCode = $matches[1];

                    if (!isset($airportCodes[$airportCode])) {
                        $airportCodes[$airportCode] = [
                            'iata' => $airportCode,
                            'urls' => [],
                        ];
                    }

                    $airportCodes[$airportCode]['urls'][] = $airportUrl;
                    $airportCodes[$airportCode]['urls'] = array_unique($airportCodes[$airportCode]['urls']);
                }
            }

            usleep(rand(300000, 800000));
        }

        if (\count($airportCodes) === 0) {
            throw new \RuntimeException('Failed to load airport codes');
        }

        $this->storage->save(
            'loungekey.airportCodes',
            $airportCodes,
            date_create('+2 month')
        );

        return $airportCodes;
    }
}

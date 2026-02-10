<?php

namespace AwardWallet\MainBundle\Service\Lounge\Parser;

use AwardWallet\MainBundle\Entity\LoungeSource;
use AwardWallet\MainBundle\Service\Lounge\CamoufoxBrowser;
use AwardWallet\MainBundle\Service\Lounge\DTO\ValueDTO;
use AwardWallet\MainBundle\Service\Lounge\HttpException;
use AwardWallet\MainBundle\Service\Lounge\LoungeHelper;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\AbstractOpeningHours;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\Builder;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\RawOpeningHours;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\StructuredOpeningHours;
use AwardWallet\MainBundle\Service\Lounge\StringFinder;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class LoungeReviewParser implements ParserInterface
{
    public const CODE = 'loungereview';

    private LoungeHelper $loungeHelper;
    private Connection $connection;
    private CamoufoxBrowser $camoufoxBrowser;
    private LoggerInterface $logger;

    public function __construct(CamoufoxBrowser $camoufoxBrowser, LoungeHelper $loungeHelper, Connection $connection, LoggerInterface $logger)
    {
        $this->loungeHelper = $loungeHelper;
        $this->connection = $connection;
        $this->camoufoxBrowser = $camoufoxBrowser;
        $this->logger = $logger;
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
        // get sitemap index
        $sitemapIndex = @\simplexml_load_string($this->get('https://loungereview.com/sitemap_index.xml'));

        if (!$sitemapIndex) {
            throw new HttpException('Sitemap index was not found');
        }

        $airportSitemapUrls = [];

        foreach ($sitemapIndex->sitemap as $sitemap) {
            $sitemapUrl = (string) $sitemap->loc;

            if (strpos($sitemapUrl, 'lounge_location_airport-sitemap') !== false) {
                $airportSitemapUrls[] = $sitemapUrl;
            }
        }

        $listAircodes = $this->connection->fetchFirstColumn("
            SELECT AirCode FROM AirCode WHERE AirCode <> ''
        ");

        foreach ($airportSitemapUrls as $airportSitemapUrl) {
            $gdPlaceSitemap = @\simplexml_load_string($this->get($airportSitemapUrl));

            if (!$gdPlaceSitemap) {
                throw new HttpException(sprintf('Sitemap "%s" was not found', $airportSitemapUrl));
            }

            foreach ($gdPlaceSitemap->url as $url) {
                $airportUrl = (string) $url->loc;

                if (preg_match('/^https:\/\/loungereview\.com\/lounges\/airport\/(\w{3})\/$/i', $airportUrl, $matches)) {
                    $airportCode = mb_strtoupper($matches[1]);

                    if (in_array($airportCode, $listAircodes) && $airportFilter($airportCode)) {
                        yield $airportCode => $this->fetchLoungesData($airportUrl);
                    }
                }
            }
        }
    }

    public function getLounge(string $airportCode, $loungeData): ?LoungeSource
    {
        $finder = StringFinder::create($loungeData['html'] ?? '');
        $url = $loungeData['url'] ?? null;

        if (!$url) {
            throw new \RuntimeException('Invalid lounge data');
        }

        if (!preg_match('/\/lounges\/([^\/]+)\/$/i', $url, $matches)) {
            throw new \RuntimeException(sprintf('Lounge ID was not found for %s', $url));
        }

        $id = $matches[1];
        $locationDto = $this->loungeHelper->parseLocation(
            [
                new ValueDTO($finder->findSingleXpath("//*[contains(@class, 'wp-block-geodirectory-geodir-widget-post-address')]//*[contains(@class, 'address_details')]/b"), false),
            ],
            [
                new ValueDTO($finder->findSingleXpath("//*[contains(@class, 'wp-block-geodirectory-geodir-widget-post-address')]//*[contains(@class, 'address_details')]")),
            ]
        );
        $carrierDto = $this->loungeHelper->parseCarrier(
            [
                new ValueDTO($finder->findSingleXpath(
                    "//*[contains(@id, 'gd-sidebar-wrapper')]//h5[contains(text(), 'Operated by')]/following-sibling::a[1]/@href",
                    '/\/operator\/(\w{2})\/$/i'
                )),
                new ValueDTO($finder->findSingleXpath("//*[contains(@id, 'gd-sidebar-wrapper')]//h5[contains(text(), 'Operated by')]/following-sibling::a[2]")),
            ],
            [new ValueDTO($finder->findSingleXpath("//*[contains(@id, 'gd-sidebar-wrapper')]//h5[contains(text(), 'Alliance')]/following-sibling::a[2]"))]
        );
        $cardsAccepted = it($finder->findXpath(
            "//div[contains(@class, 'accesspolicy_cards')]//*[contains(@class, 'cards_accepted_card')]//img/@src",
        ))->map(function (string $src) {
            return preg_match('/\/cards\/([^\/\.]+)\.(?:png|jpg)/i', $src, $matches) ? $matches[1] : null;
        })->filterNotNull()->toArray();
        $isClosed = !empty(
            $finder->findSingleXpath(
                "//*[contains(@class, 'badges-container')]//i[following-sibling::text()[contains(.,'CLOSED')]]/@class"
            )
        );
        $isRestaurant = !empty(
            $finder->findSingleXpath("//*[contains(@class, 'badges-container')]//i[contains(@class, 'fa-utensils')]/@class")
        ) || !empty(
            $finder->findSingleXpath("//*[contains(@id, 'gd-sidebar-wrapper')]//*[contains(@class, 'company_info2')]/*[contains(text(), 'Restaurant')]")
        );

        $lounge = (new LoungeSource())
            ->setAirportCode($airportCode)
            ->setSourceId($id)
            ->setName(trim($finder->findSingleXpath("//header[contains(@class, 'article-header')]/h1")))
            ->setTerminal($locationDto->getTerminal())
            ->setGate($locationDto->getGate())
            ->setGate2($locationDto->getGate2())
            ->setOpeningHours($this->parseOpeningHours($airportCode, $finder))
            ->setAvailable(
                empty($finder->findSingleXpath(
                    "//*[contains(@id, 'gd-sidebar-wrapper')]//*[contains(@class, 'geodir_post_meta')]//*[contains(@class, 'geodir-i-time')]",
                    '/\b(?:closed temporarily)|(?:permanently closed)\b/ims'
                )) && !$isClosed
            )
            ->setLocation($finder->findSingleXpath("//*[contains(@class, 'wp-block-geodirectory-geodir-widget-post-address')]//*[contains(@class, 'address_details')]"))
            ->setAdditionalInfo(null)
            ->setAmenities($this->parseAmenities($finder))
            ->setRules($finder->findSingleXpath("//div[contains(@class, 'accesspolicy_details')]"))
            ->setIsRestaurant($isRestaurant ? true : null)
            ->setImages(
                $finder->findXpath("//*[contains(@class, 'geodir-images')]/*[contains(@class, 'carousel-item')]/img/@data-src")
            )
            ->setUrl($url)
            ->setPriorityPassAccess(in_array('prioritypass', $cardsAccepted) ? true : null)
            ->setAmexPlatinumAccess(in_array('americanexpress', $cardsAccepted) ? true : null)
            ->setDragonPassAccess(in_array('dragonpass', $cardsAccepted) ? true : null)
            ->setLoungeKeyAccess(in_array('loungekey', $cardsAccepted) ? true : null)
            ->setAirlines($carrierDto->getAirlines())
            ->setAlliances($carrierDto->getAlliances());

        return $lounge;
    }

    private function fetchLoungesData(string $airportUrl): array
    {
        $finder = StringFinder::create($this->get($airportUrl));

        return it($finder->findXpath('//*[@data-post-id]//h2/a/@href'))
//            ->onEach(fn () => usleep(rand(300000, 1000000)))
            ->map(function (string $loungeUrl) {
                return [
                    'html' => $this->get($loungeUrl),
                    'url' => $loungeUrl,
                ];
            })
            ->toArray();
    }

    private function parseOpeningHours(string $airportCode, StringFinder $finder): ?AbstractOpeningHours
    {
        $tz = $this->loungeHelper->getAirportTimezone($airportCode);
        $weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $oh = [];

        foreach ($weekDays as $i => $day) {
            $open = $finder->findXpath("//*[contains(@id, 'gd-sidebar-wrapper')]//*[contains(@class, 'gd-bh-open-hours')]/*[@data-day='" . ($i + 1) . "']//*[@data-open]/@data-open");
            $close = $finder->findXpath("//*[contains(@id, 'gd-sidebar-wrapper')]//*[contains(@class, 'gd-bh-open-hours')]/*[@data-day='" . ($i + 1) . "']//*[@data-close]/@data-close");

            if (count($open) === 1 && count($close) === 1 && $open[0] === '0000' && $close[0] === '0000') {
                $oh[$day] = [
                    '00:00-23:59',
                    'data' => [
                        'code' => Builder::CODE_OPEN24,
                    ],
                ];
            } elseif (count($open) > 0 && count($open) === count($close)) {
                $periods = [];

                for ($j = 0; $j < count($open); $j++) {
                    $formattedOpen = null;
                    $formattedClose = null;

                    if (!empty($open[$j]) && mb_strlen($open[$j]) === 4) {
                        $formattedOpen = substr($open[$j], 0, 2) . ':' . substr($open[$j], 2, 2);
                    }

                    if (!empty($close[$j]) && mb_strlen($close[$j]) === 4) {
                        $formattedClose = substr($close[$j], 0, 2) . ':' . substr($close[$j], 2, 2);
                    }

                    if ($formattedOpen && $formattedClose) {
                        $periods[] = sprintf('%s-%s', $formattedOpen, $formattedClose);
                    } elseif ($formattedOpen && !$formattedClose) {
                        $periods[] = [
                            sprintf('%s-23:59', $formattedOpen),
                            'data' => [
                                'code' => Builder::CODE_RANGE_UNKNOWN_END,
                                'msg' => $close[$j],
                            ],
                        ];
                    } elseif (!$formattedOpen && $formattedClose) {
                        $periods[] = [
                            sprintf('00:00-%s', $formattedClose),
                            'data' => [
                                'code' => Builder::CODE_RANGE_UNKNOWN_START,
                                'msg' => $open[$j],
                            ],
                        ];
                    } elseif (!$formattedOpen && !$formattedClose) {
                        $periods[] = [
                            '00:00-23:59',
                            'data' => [
                                'code' => Builder::CODE_RANGE_UNKNOWN_BOTH,
                                'msg' => sprintf('%s|%s', $open[$j], $close[$j]),
                            ],
                        ];
                    }
                }

                $oh[$day] = $periods;
            } elseif (
                ($raw = $finder->findSingleXpath("//*[contains(@id, 'gd-sidebar-wrapper')]//*[contains(@class, 'geodir-i-time')]"))
                && !preg_match('/\b(?:closed|temporarily)\b/i', $raw)
            ) {
                return new RawOpeningHours($raw);
            }
        }

        if (count($oh) === 7) {
            return new StructuredOpeningHours($tz, $oh);
        }

        return null;
    }

    private function parseAmenities(StringFinder $finder): ?string
    {
        $amenities = $finder->findXpath("//*[contains(@id, 'gd-sidebar-wrapper')]//img[contains(@class, 'amenities_sprite') and @data-src]/@alt");

        if (count($amenities) === 0) {
            return null;
        }

        $yes = it($amenities)
            ->map(function (string $amenity) {
                return preg_match('/^(.+)\s*:\s+yes\s*$/i', $amenity, $matches) ? $matches[1] : null;
            })
            ->filterNotNull()
            ->toArray();
        $no = it($amenities)
            ->map(function (string $amenity) {
                return preg_match('/^(.+)\s*:\s+no\s*$/i', $amenity, $matches) ? $matches[1] : null;
            })
            ->filterNotNull()
            ->toArray();

        $rows = [];

        if ($yes) {
            $rows[] = sprintf('Yes: %s', implode(', ', $yes));
        }

        if ($no) {
            $rows[] = sprintf('No: %s', implode(', ', $no));
        }

        return implode('; ', $rows);
    }

    private function get(string $url): string
    {
        $result = $this->camoufoxBrowser->navigate($url);

        if ($result === null) {
            throw new HttpException("failed to download $url");
        }

        $html = $result->getHtml();
        $this->logger->info("downloaded $url, " . strlen($html) . " bytes");

        return $html;
    }
}

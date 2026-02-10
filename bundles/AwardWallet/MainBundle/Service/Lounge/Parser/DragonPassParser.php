<?php

namespace AwardWallet\MainBundle\Service\Lounge\Parser;

use AwardWallet\MainBundle\Entity\LoungeSource;
use AwardWallet\MainBundle\Service\Lounge\CurlBrowser;
use AwardWallet\MainBundle\Service\Lounge\DTO\ValueDTO;
use AwardWallet\MainBundle\Service\Lounge\HttpException;
use AwardWallet\MainBundle\Service\Lounge\LoungeHelper;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\RawOpeningHours;
use AwardWallet\MainBundle\Service\Lounge\StringFinder;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class DragonPassParser implements ParserInterface
{
    public const CODE = 'dragonPass';

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
        $headers = [
            'X-Requested-With' => 'XMLHttpRequest',
            'Referer' => 'https://en.dragonpass.com.cn/airports',
        ];
        $json = $this->browser->json('https://en.dragonpass.com.cn/airports/list?type=-1', 'get', null, $headers);

        if (!is_array($json) || !is_array($json['data']['list'] ?? null)) {
            throw new HttpException('Airport list was not found');
        }

        yield from it($json['data']['list'])
            ->filter(function (array $airportData) use ($airportFilter) {
                return !empty($airportData['iataCode'])
                    && $airportFilter($airportData['iataCode'])
                    && isset($airportData['pmCodes'])
                    && is_array($airportData['pmCodes'])
                    && in_array('lounge', $airportData['pmCodes']);
            })
            ->reindexByColumn('iataCode')
            ->onEach(fn () => usleep(rand(300000, 1000000)))
            ->map(function (array $airportData) use ($headers) {
                $airportJson = $this->browser->json(
                    'https://en.dragonpass.com.cn/lounge/pagelist',
                    'post',
                    [
                        'id' => $airportData['id'],
                        'page' => 1,
                        'maxResult' => rand(40, 50),
                    ],
                    $headers
                );

                if (!is_array($airportJson) || !is_array($airportJson['data']['loungeList'] ?? null)) {
                    throw new HttpException(sprintf('Lounge list for %s (%d) was not found', $airportData['iataCode'], $airportData['id']));
                }

                return array_map(function (array $lounge) use ($airportData, $headers) {
                    if (is_null($id = $lounge['lounge']['id'] ?? null)) {
                        throw new HttpException(sprintf('Lounge ID was not found for %s (%d)', $airportData['iataCode'], $airportData['id']));
                    }

                    $this->browser->get(
                        $url = sprintf('https://en.dragonpass.com.cn/lounge/%d', $id),
                        $headers
                    );

                    return array_merge($lounge, [
                        'airport' => $airportData,
                        'html' => $this->browser->getResponse(),
                        'url' => $url,
                    ]);
                }, $airportJson['data']['loungeList']);
            });
    }

    public function getLounge(string $airportCode, $loungeData): ?LoungeSource
    {
        $finder = StringFinder::create($loungeData['html'] ?? '');
        $airportData = $loungeData['airport'] ?? null;
        $loungeDetails = $loungeData['lounge'] ?? null;
        $productData = $loungeData['products'] ?? null;
        $url = $loungeData['url'] ?? null;

        if (!$airportData || !$loungeDetails || !$productData || !$url) {
            throw new \RuntimeException('Invalid lounge data');
        }

        $locationDto = $this->loungeHelper->parseLocation(
            [new ValueDTO($loungeDetails['terminal'] ?? null, false)],
            [
                new ValueDTO($loungeDetails['nearestGate'] ?? null, false),
                new ValueDTO($loungeDetails['boardingGate'] ?? null, false),
                new ValueDTO($loungeDetails['locationGuide'] ?? null),
            ]
        );
        $carrierDto = $this->loungeHelper->parseCarrier(
            [new ValueDTO($loungeDetails['name'] ?? null)],
            [new ValueDTO($loungeDetails['name'] ?? null)]
        );

        // remove " - Set meal" from Name
        $loungeDetails['name'] = trim(preg_replace('/\s*-\s*Set\smeal\s*$/i', '', $loungeDetails['name']));

        $lounge = (new LoungeSource())
            ->setAirportCode($airportCode)
            ->setName($loungeDetails['name'])
            ->setTerminal($locationDto->getTerminal())
            ->setGate($locationDto->getGate())
            ->setGate2($locationDto->getGate2())
            ->setOpeningHours($loungeDetails['businessHours'] ? new RawOpeningHours($loungeDetails['businessHours']) : null)
            ->setAvailable($productData['status'] == 1)
            ->setLocation($loungeDetails['locationGuide'] ?? null)
            ->setAdditionalInfo(null)
            ->setAmenities(
                implode(', ', $finder->findXpath("//*[contains(@class,'DetailAmenities')]//*[contains(@class,'text')]"))
            )
            ->setRules($finder->findXpath("//*[contains(@class,'ConditionsText')]")[0] ?? null)
            ->setSourceId($loungeDetails['id'])
            ->setImages(
                it($finder->findXpath("//*[contains(@class,'swiper-slide')]/img/@src"))
                    ->map(function (string $url) {
                        if (strpos($url, '?') !== false) {
                            $url = substr($url, 0, strpos($url, '?'));
                        }

                        if (strpos($url, '//') === 0) {
                            return 'https:' . $url;
                        }

                        if (strpos($url, '/') === 0) {
                            return null;
                        }

                        return $url;
                    })
                    ->filterNotNull()
                    ->toArray()
            )
            ->setUrl($url)
            ->setPriorityPassAccess(null)
            ->setAmexPlatinumAccess(null)
            ->setDragonPassAccess(true)
            ->setLoungeKeyAccess(null)
            ->setAirlines($carrierDto->getAirlines())
            ->setAlliances($carrierDto->getAlliances());

        return $lounge;
    }
}

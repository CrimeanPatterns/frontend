<?php

namespace AwardWallet\MainBundle\Service\Lounge\Parser;

use AwardWallet\MainBundle\Entity\LoungeSource;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Lounge\CurlBrowser;
use AwardWallet\MainBundle\Service\Lounge\DTO\ValueDTO;
use AwardWallet\MainBundle\Service\Lounge\HttpException;
use AwardWallet\MainBundle\Service\Lounge\LoungeHelper;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\RawOpeningHours;
use AwardWallet\MainBundle\Service\Lounge\StringFinder;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class PriorityPassParser implements ParserInterface
{
    public const CODE = 'priorityPass';

    private CurlBrowser $browser;

    private LoungeHelper $loungeHelper;

    private string $baseUrl = 'https://www.prioritypass.com';

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
        $json = $this->browser->json($this->baseUrl . '/api/search/airportmapsearch');

        if (!$json || !isset($json['Results']) || !is_array($json['Results']) || count($json['Results']) === 0) {
            throw new HttpException('Airports data was not found');
        }

        $airportsData = it($json['Results'])->filter(function (array $airport) use ($airportFilter): bool {
            if (
                !$airportFilter($airport['Code'])
                || (isset($airport['HasActiveLounges']) && !$airport['HasActiveLounges'])
                || !isset($airport['LocationId'])
            ) {
                return false;
            }

            return true;
        })->reindexByColumn('Code');

        foreach ($airportsData as $code => $airportData) {
            if (!$this->browser->post(
                sprintf('%s/api/sitecore/TransportHub/TerminalCarousels?locationId=%s&transportHubPageId=%s', $this->baseUrl, $airportData['LocationId'], $airportData['ItemId'])
            )) {
                continue;
            }

            $lounges = [];
            $urls = $this->browser->findXpath("//a[starts-with(@href, '/en/lounges/') or starts-with(@href, '/lounges/')]/@href");

            foreach ($urls as $url) {
                try {
                    $this->browser->get($this->baseUrl . $url);
                } catch (HttpException $e) {
                    if ($e->getCode() == 404) {
                        continue;
                    }

                    throw $e;
                }

                $lounges[] = [
                    'url' => $this->baseUrl . $url,
                    'body' => $this->browser->getResponse(),
                ];
            }

            yield $code => $lounges;
        }
    }

    public function getLounge(string $airportCode, $loungeData): ?LoungeSource
    {
        $finder = StringFinder::create($loungeData['body']);

        if (StringHandler::isEmpty($name = $finder->findSingleXpath("//div[contains(@class, 'airport-content')]/h1/text()"))) {
            if (StringHandler::isEmpty($name = $finder->findSingleXpath('//title', '^(.*?)\s+' . preg_quote($airportCode) . '\b'))) {
                throw new \RuntimeException(sprintf('PriorityPass lounge name is empty for %s', $loungeData['url']));
            }
        }

        $openingHours = $finder->findSingleXpath("//article[normalize-space(h4) = 'Opening Hours']", "#^\s*Opening Hours\s*(.+)#s");
        $location = $finder->findSingleXpath("//article[normalize-space(h4) = 'Location']", "#^\s*Location\s*(.+)#s");
        $locationDto = $this->loungeHelper->parseLocation(
            [new ValueDTO($finder->findSingleXpath("//div[contains(@class, 'airport-content')]/h5/text()"))],
            [new ValueDTO($location)]
        );
        $carrierDto = $this->loungeHelper->parseCarrier(
            [new ValueDTO($name)],
            [new ValueDTO($name)]
        );

        $rules = $finder->findSingleXpath("//article[normalize-space(.//h4) = 'Conditions']", "#^\s*Conditions\s*(.+)#s");
        $isRestaurant = $rules && preg_match("/Cardholders can use their lounge visit entitlement to receive .+? off the bill/i", $rules) ? true : null;

        $lounge = (new LoungeSource())
            ->setAirportCode($airportCode)
            ->setName($name)
            ->setTerminal($locationDto->getTerminal())
            ->setGate($locationDto->getGate())
            ->setGate2($locationDto->getGate2())
            ->setOpeningHours($openingHours ? new RawOpeningHours($openingHours) : null)
            ->setAvailable(preg_match("/(lounge is temporarily closed|the area .* is temporarily closed)/", $openingHours) ? false : true)
            ->setLocation($location)
            ->setAdditionalInfo($finder->findSingleXpath("//article[normalize-space(.//h4) = 'Additional Information']", "#^\s*Additional Information\s*(.+)#s"))
            ->setAmenities(implode(', ', $finder->findXpath("//article[normalize-space(.//h4) = 'Facilities']//text()[normalize-space() and not(ancestor::h4)]")))
            ->setRules($rules)
            ->setIsRestaurant($isRestaurant)
            ->setSourceId(
                str_replace(
                    '/',
                    '-',
                    preg_replace('/^(.+?\/lounges\/)/', '', $loungeData['url'])
                )
            )
            ->setImages($finder->findXpath("//div[contains(@class,'slide')]/img/@src"))
            ->setUrl($loungeData['url'])
            ->setPriorityPassAccess(true)
            ->setAmexPlatinumAccess(null)
            ->setDragonPassAccess(null)
            ->setLoungeKeyAccess(null)
            ->setAirlines($carrierDto->getAirlines())
            ->setAlliances($carrierDto->getAlliances());

        return $lounge;
    }
}

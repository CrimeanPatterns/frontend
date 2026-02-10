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

class LoungeBuddyParser implements ParserInterface
{
    public const CODE = 'loungebuddy';

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
        return false;
    }

    public function isParsingFrozen(): bool
    {
        return true;
    }

    public function requestAirports(callable $airportFilter): iterable
    {
        $this->browser->resetProxyList();
        $this->browser->setDefaultHeaders(['Referer' => 'https://www.loungebuddy.com/']);

        if ($airports = $this->browser->json('https://www.loungebuddy.com/api/locations')) {
            if (!isset($airports['groupedAllAirports'])) {
                throw new HttpException('Airports data was not found');
            }

            foreach (
                it($airports['groupedAllAirports'])
                ->flatten(1)
                ->reindexByColumn('IATA')
                ->filter(function (array $airportData) use ($airportFilter) {
                    return $airportFilter($airportData['IATA'])
                        && (($airportData['purchasableLoungeCount'] ?? 0) + ($airportData['traditionalLoungeCount'] ?? 0)) > 0;
                }) as $code => $airportData
            ) {
                $lounges = $this->browser->json("https://www.loungebuddy.com/api/airports/$code?source=homepage");

                if (
                    count($lounges['regularLounges'] ?? []) === 0
                    && count($lounges['purchasableLounges'] ?? []) === 0
                ) {
                    continue;
                }

                // AmexPlatinumAccess
                try {
                    $amexPlatinumAccessLounges = $this->browser->json("https://global.americanexpress.com/api/feebasedsrvcs/v1/lounge_finder/airports/$code/lounges?product=the-platinum-card-idc-en&locale=en-US&lang=en-US", 'get', null, [
                        'Referer' => 'https://global.americanexpress.com/',
                    ]);
                    $amexPlatinumAccessLounges = it($amexPlatinumAccessLounges ?? [])
                        ->column('id')
                        ->toArray();
                } catch (HttpException $e) {
                    if ($e->getCode() !== 404) {
                        throw $e;
                    }

                    $amexPlatinumAccessLounges = [];
                }

                $lounges = array_merge($lounges['regularLounges'] ?? [], $lounges['purchasableLounges'] ?? []);

                foreach ($lounges as &$lounge) {
                    $details = $this->browser->json("https://www.loungebuddy.com/api/lounges/{$lounge['id']}?currency=USD&source=homepage");

                    if (!$details || !isset($details['lounge'])) {
                        continue;
                    }

                    $lounge['loungeDetails'] = array_merge([
                        'amexPlatinumAccess' => in_array($lounge['id'], $amexPlatinumAccessLounges),
                        'amenities' => $details['amenities'],
                        'images' => it($details['images'])->filterHasColumn('original')->map(function (array $img) {
                            return [
                                'src' => $img['original'],
                                'description' => $img['description'] ?? null,
                            ];
                        })->toArray(),
                    ], array_intersect_key($details['lounge'], array_flip([
                        'accessRules',
                        'airport',
                        'description',
                        'importantInformation',
                        'location',
                        'nonContingentAccess',
                        'price',
                    ])));
                }

                yield $code => $lounges;
            }
        } else {
            throw new HttpException('Airports data was not found');
        }
    }

    public function getLounge(string $airportCode, $loungeData): ?LoungeSource
    {
        $name = trim(preg_replace('/\(([^\(]+)?\bclosed\b([^\)]*)?\)/ims', '', $loungeData['name'] ?? ''));

        if (empty($name)) {
            throw new \RuntimeException(sprintf('LoungeBuddy lounge name is empty for airport %s', $airportCode));
        }

        $info = array_merge(
            array_map(fn (string $info) => sprintf('[General] %s', $info), $loungeData['importantInformation']['general'] ?? []),
            array_map(fn (string $info) => sprintf('[Regulatory] %s', $info), $loungeData['importantInformation']['regulatory'] ?? []),
            array_map(fn (string $info) => sprintf('[Warning] %s', $info), $loungeData['importantInformation']['warning'] ?? [])
        );

        $locationDto = $this->loungeHelper->parseLocation(
            [new ValueDTO($loungeData['location']['terminal'] ?? null, false)],
            [new ValueDTO($loungeData['location']['terminalDirections'] ?? null)]
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
                $loungeData['airport']['timeZone'] ?? $loungeData['timezone'] ?? 'UTC'
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
            ->setLocation($loungeData['location']['terminalDirections'] ?? null)
            ->setAdditionalInfo($loungeData['description'] ?? null)
            ->setAmenities(
                it($loungeData['amenities'] ?? [])
                    ->column('name')
                    ->joinToString(', ')
            )
            ->setRules(count($info) > 0 ? implode("\n", $info) : null)
            ->setSourceId($loungeData['id'])
            ->setImages(it($loungeData['loungeDetails']['images'] ?? [])->column('src')->toArray())
            ->setUrl("https://www.loungebuddy.com/{$loungeData['displayIATA']}/{$loungeData['slugPath']}?source=homepage")
            ->setPriorityPassAccess(null)
            ->setAmexPlatinumAccess($loungeData['loungeDetails']['amexPlatinumAccess'] ?? false)
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
                return new StructuredOpeningHours($tz, OpeningHoursParser::parse($hours));
            }
        } catch (\InvalidArgumentException $e) {
        }

        return null;
    }
}

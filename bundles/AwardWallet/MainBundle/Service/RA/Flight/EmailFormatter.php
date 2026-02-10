<?php

namespace AwardWallet\MainBundle\Service\RA\Flight;

use AwardWallet\Common\CurrencyConverter\CurrencyConverter;
use AwardWallet\Common\Entity\Geotag;
use AwardWallet\Common\Geo\GeoCodingFailedException;
use AwardWallet\Common\Geo\GoogleGeo;
use AwardWallet\MainBundle\Entity\MileValue;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\RAFlightSearchQuery;
use AwardWallet\MainBundle\Entity\RAFlightSearchRoute;
use AwardWallet\MainBundle\Entity\RAFlightSearchRouteSegment;
use AwardWallet\MainBundle\Entity\Repositories\AirlineRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Translator\EntityTranslator;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Timeline\PhoneBookFactory;
use AwardWallet\MainBundle\Timeline\TripInfo\TripInfo;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class EmailFormatter implements TranslationContainerInterface
{
    private TranslatorInterface $translator;

    private EntityTranslator $entityTranslator;

    private LocalizeService $localizeService;

    private PhoneBookFactory $phoneBookFactory;

    private ProviderRepository $providerRepository;

    private AirlineRepository $airlineRepository;

    private GoogleGeo $geoCoder;

    private CurrencyConverter $currencyConverter;

    private LoggerInterface $logger;

    private bool $debug;

    public function __construct(
        TranslatorInterface $translator,
        EntityTranslator $entityTranslator,
        LocalizeService $localizeService,
        PhoneBookFactory $phoneBookFactory,
        ProviderRepository $providerRepository,
        AirlineRepository $airlineRepository,
        GoogleGeo $geoCoder,
        CurrencyConverter $currencyConverter,
        LoggerFactory $loggerFactory,
        bool $debug
    ) {
        $this->translator = $translator;
        $this->entityTranslator = $entityTranslator;
        $this->localizeService = $localizeService;
        $this->phoneBookFactory = $phoneBookFactory;
        $this->providerRepository = $providerRepository;
        $this->airlineRepository = $airlineRepository;
        $this->geoCoder = $geoCoder;
        $this->currencyConverter = $currencyConverter;
        $this->logger = $loggerFactory->createLogger($loggerFactory->createProcessor([
            'class' => 'EmailFormatter',
        ]));
        $this->debug = $debug;
    }

    /**
     * @param RAFlightSearchRoute[] $routes
     */
    public function format(MileValue $mileValue, array $routes, Usr $user): array
    {
        $trip = $mileValue->getTrip();

        if (!$trip) {
            throw new \InvalidArgumentException('Trip is required');
        }

        if (empty($routes)) {
            throw new \InvalidArgumentException('Routes are required');
        }

        $locale = $user->getLocale();
        $provider = $mileValue->getProvider();
        $phoneBook = $this->phoneBookFactory->create([$trip], $user);
        $phone = $phoneBook->getMostImportantPhone();
        $sortedSegments = array_values($trip->getSegmentsSorted());
        $countSegments = count($sortedSegments);
        $emailData = [
            'destination' => $sortedSegments[$countSegments - 1]->getArrAirportName(),
            'existing' => [
                'airline_name' => $provider->getDisplayname(),
                'airline_code' => $provider->getCode(),
                'passengers' => $mileValue->getTravelersCount(),
                'policy' => htmlspecialchars_decode($provider->getAwardChangePolicy()),
                'phone' => $phone,
                'dep_date' => $this->localizeService->patternDateTime(
                    $trip->getStartDate(),
                    'cccc, MMMM d, yyyy',
                    $locale
                ),
                'conf_no' => $trip->getConfirmationNumber(),
                'cost' => sprintf(
                    '%s %s',
                    $this->localizeService->formatNumber($mileValue->getTotalMilesSpent(), 1, $locale),
                    $this->entityTranslator->transChoice(
                        $provider->getCurrency(),
                        'plural',
                        $mileValue->getTotalMilesSpent(),
                        [],
                        'currency',
                        $locale
                    )
                ),
                'total_charge' => $this->localizeService->formatCurrency(
                    $trip->getPricingInfo()->getTotal(),
                    $trip->getPricingInfo()->getCurrencyCode(),
                    true,
                    $locale
                ),
                'duration' => $this->formatDuration(
                    $trip->getUTCStartDate(),
                    $trip->getUTCEndDate(),
                    $locale
                ),
                'segments' => [],
            ],
        ];

        $tripStartDate = $trip->getStartDate();

        foreach ($sortedSegments as $index => $tripSegment) {
            $segmentEmailData = [
                'from' => [
                    'name' => $tripSegment->getDepAirportName(),
                    'time' => $this->formatTime($tripSegment->getDepartureDate(), $tripStartDate, $locale),
                ],
                'to' => [
                    'name' => $tripSegment->getArrAirportName(),
                    'time' => $this->formatTime($tripSegment->getArrivalDate(), $tripStartDate, $locale),
                ],
            ];

            $operatingAirline = $this->resolveAirline($tripSegment);
            $flightNumber = $tripSegment->getFlightNumber();

            if ($operatingAirline) {
                $segmentEmailData['airline'] = $operatingAirline;

                if (!empty($flightNumber)) {
                    $segmentEmailData['airline'] .= ' ' . $flightNumber;
                }
            }

            if (!empty($cabinClass = $tripSegment->getCabinClass())) {
                $segmentEmailData['cabin'] = $cabinClass;
            }

            if ($index < $countSegments - 1) {
                $nextSegment = $sortedSegments[$index + 1];
                $segmentEmailData['layover'] = $this->formatDuration(
                    $tripSegment->getUTCEndDate(),
                    $nextSegment->getUTCStartDate(),
                    $locale
                );
            }

            $emailData['existing']['segments'][] = $segmentEmailData;
        }

        $resultProviders = $this->providerRepository->findBy([
            'code' => it($routes)
                ->map(function (RAFlightSearchRoute $route) {
                    return $route->getMileCostProgram();
                })
                ->filter(fn ($programCode) => !empty($programCode))
                ->unique()
                ->toArray(),
        ]);
        /** @var Provider[] $resultProviders */
        $resultProviders = it($resultProviders)
            ->reindex(fn (Provider $provider) => $provider->getCode())
            ->toArrayWithKeys();

        $emailData['found_routes'] = it($routes)
            ->map(function (RAFlightSearchRoute $route) use ($locale, $provider, $trip, $mileValue, $resultProviders) {
                $routeTickets = $route->getTickets() ?? $mileValue->getTravelersCount();

                if (!isset($resultProviders[$route->getMileCostProgram()]) || is_null($route->getMileCost())) {
                    $this->logger->warning(sprintf(
                        'Provider not found or mile cost is not set for route %d',
                        $route->getId()
                    ));

                    return null;
                }

                $providerColor = $this->getProviderColor($resultProviders[$route->getMileCostProgram()]->getCode());

                if (is_null($providerColor) && !$this->debug) {
                    $this->logger->warning(sprintf(
                        'Color not found for provider %s',
                        $resultProviders[$route->getMileCostProgram()]->getCode()
                    ));

                    return null;
                }

                // create doctrine entity for each route segment only for formatting purposes
                $entityTrip = new Trip();
                $entityTrip->setCategory(TRIP_CATEGORY_AIR);

                $routeSegments = array_map(function (RAFlightSearchRouteSegment $segment) use ($entityTrip, $locale) {
                    $entitySegment = new Tripsegment();
                    $entitySegment->setTripid($entityTrip);
                    $entitySegment->setDepcode($segment->getDepCode());
                    $entitySegment->setDepgeotagid($depGeoTag = $this->findGeoTag($segment->getDepCode()));
                    $entitySegment->setDepname($depGeoTag ? $depGeoTag->getAddress() : $segment->getDepCode());
                    $entitySegment->setDepartureDate($segment->getDepDate());
                    $entitySegment->setArrcode($segment->getArrCode());
                    $entitySegment->setArrgeotagid($arrGeoTag = $this->findGeoTag($segment->getArrCode()));
                    $entitySegment->setArrname($arrGeoTag ? $arrGeoTag->getAddress() : $segment->getArrCode());
                    $entitySegment->setArrivalDate($segment->getArrDate());
                    $entitySegment->setCabinClass($this->convertCabin($segment->getService(), $locale));

                    if (!empty($airlineCode = $segment->getAirlineCode())) {
                        $entitySegment->setOperatingAirline($this->airlineRepository->findOneBy(['code' => $airlineCode]));
                    }

                    $entitySegment->setFlightNumber(
                        $segment->getFlightNumbers() ? $segment->getFlightNumbers()[0] : null
                    );

                    return $entitySegment;
                }, $route->getSegments()->toArray());

                $routeMileCost = $route->getMileCost() * $routeTickets;
                $routeCashCost = $route->getTotalTaxesAndFees() * $routeTickets;
                $routeCurrency = $route->getCurrency();

                if (isset($routeCurrency) && strtolower($routeCurrency) !== 'usd') {
                    $this->logger->warning('Currency is not USD', [
                        'route' => $route->getId(),
                        'currency' => $routeCurrency,
                    ]);

                    return null;
                }

                $emailData = [
                    'airline_name' => $resultProviders[$route->getMileCostProgram()]->getDisplayname(),
                    'airline_code' => $resultProviders[$route->getMileCostProgram()]->getCode(),
                    'airline_color' => $providerColor,
                    'dep_date' => $this->localizeService->patternDateTime(
                        $entityTrip->getStartDate(),
                        'cccc, MMMM d, yyyy',
                        $locale
                    ),
                    'cost' => sprintf(
                        '%s %s',
                        $this->localizeService->formatNumber($routeMileCost, 1, $locale),
                        $this->entityTranslator->transChoice(
                            $provider->getCurrency(),
                            'plural',
                            $routeMileCost,
                            [],
                            'currency',
                            $locale
                        )
                    ),
                    'total_charge' => $this->localizeService->formatCurrency(
                        $routeCashCost,
                        $route->getCurrency() ?? 'USD',
                        true,
                        $locale
                    ),
                    'duration' => $this->formatDuration(
                        date_create(),
                        date_create('+' . $route->getTotalDuration() . ' seconds'),
                        $locale
                    ),
                    'check_availability_url' => $resultProviders[$route->getMileCostProgram()]->getSite(),
                    'segments' => [],
                ];

                $tripStartDate = $entityTrip->getStartDate();
                $countSegments = count($routeSegments);

                foreach ($routeSegments as $index => $segment) {
                    $segmentEmailData = [
                        'from' => [
                            'name' => $segment->getDepAirportName(),
                            'time' => $this->formatTime($segment->getDepartureDate(), $tripStartDate, $locale),
                        ],
                        'to' => [
                            'name' => $segment->getArrAirportName(),
                            'time' => $this->formatTime($segment->getArrivalDate(), $tripStartDate, $locale),
                        ],
                    ];

                    $operatingAirline = $segment->getOperatingAirline();
                    $flightNumber = $segment->getFlightNumber();

                    if ($operatingAirline) {
                        $segmentEmailData['airline'] = $operatingAirline->getName();

                        if (!empty($flightNumber)) {
                            $segmentEmailData['airline'] .= ' ' . $flightNumber;
                        }
                    }

                    if (!empty($cabinClass = $segment->getCabinClass())) {
                        $segmentEmailData['cabin'] = $cabinClass;
                    }

                    if ($index < $countSegments - 1) {
                        $nextSegment = $routeSegments[$index + 1];
                        $segmentEmailData['layover'] = $this->formatDuration(
                            $segment->getUTCEndDate(),
                            $nextSegment->getUTCStartDate(),
                            $locale
                        );
                    }

                    $emailData['segments'][] = $segmentEmailData;
                }

                $emailData['changes'] = [];

                // compare duration
                $currentTotalDurationSec = $trip->getUTCEndDate()->getTimestamp() - $trip->getUTCStartDate()->getTimestamp();
                $routeDurationSec = $entityTrip->getUTCEndDate()->getTimestamp() - $entityTrip->getUTCStartDate()->getTimestamp();

                if ($currentTotalDurationSec != $routeDurationSec) {
                    $emailData['changes'][] = [
                        'kind' => 'duration',
                        'duration' => $this->formatDuration(
                            date_create(),
                            date_create('+' . abs($currentTotalDurationSec - $routeDurationSec) . ' seconds'),
                            $locale
                        ),
                        'isBeneficial' => $currentTotalDurationSec > $routeDurationSec,
                        'percentage' => $this->localizeService->formatNumber(
                            abs($currentTotalDurationSec - $routeDurationSec) / $currentTotalDurationSec * 100,
                            0,
                            $locale
                        ) . '%',
                    ];
                }

                // compare cost in miles
                $currentMileCost = $mileValue->getTotalMilesSpent();

                if ($currentMileCost != $routeMileCost) {
                    $emailData['changes'][] = [
                        'kind' => 'cost',
                        'amount' => $this->localizeService->formatNumber(abs($currentMileCost - $routeMileCost), 0, $locale),
                        'currency' => $this->entityTranslator->transChoice(
                            $provider->getCurrency(),
                            'plural',
                            abs($currentMileCost - $routeMileCost),
                            [],
                            'currency',
                            $locale
                        ),
                        'percentage' => $this->localizeService->formatNumber(
                            abs($currentMileCost - $routeMileCost) / $currentMileCost * 100,
                            0,
                            $locale
                        ) . '%',
                        'isBeneficial' => $currentMileCost > $routeMileCost,
                    ];
                }

                if (!it($emailData['changes'])->any(fn (array $change) => $change['isBeneficial'])) {
                    $this->logger->warning('No beneficial changes found', [
                        'route' => $route->getId(),
                        'currentMileCost' => $currentMileCost,
                        'routeMileCost' => $routeMileCost,
                        'routeTickets' => $routeTickets,
                    ]);

                    return null;
                }

                // compare cost in cash
                $currentCurrency = $trip->getPricingInfo()->getCurrencyCode() ?? 'USD';

                if (strtolower($currentCurrency) !== 'usd') {
                    $totalSpentInLocalCurrency = $mileValue->getTotalSpentInLocalCurrency();

                    if (
                        isset($totalSpentInLocalCurrency)
                        && $totalSpentInLocalCurrency === $trip->getPricingInfo()->getTotal()
                        && $mileValue->getLocalCurrency() === $currentCurrency
                    ) {
                        $currentCashCost = (float) $mileValue->getTotalTaxesSpent();
                    } else {
                        $currentCashCost = $this->currencyConverter->convertToUsd(
                            $trip->getPricingInfo()->getTotal(),
                            $currentCurrency
                        );
                    }
                } else {
                    $currentCashCost = (float) $mileValue->getTotalTaxesSpent();
                }

                if (!empty($currentCashCost) && $currentCashCost != $routeCashCost) {
                    $emailData['changes'][] = [
                        'kind' => 'taxes',
                        'amount' => $this->localizeService->formatCurrency(
                            abs($currentCashCost - $routeCashCost),
                            $routeCurrency,
                            true,
                            $locale
                        ),
                        'isBeneficial' => $currentCashCost > $routeCashCost,
                        'percentage' => $this->localizeService->formatNumber(
                            abs($currentCashCost - $routeCashCost) / $currentCashCost * 100,
                            0,
                            $locale
                        ) . '%',
                    ];
                }

                // sort changes by beneficial
                $emailData['changes'] = it($emailData['changes'])
                    ->usort(fn (array $a, array $b) => $b['isBeneficial'] <=> $a['isBeneficial'])
                    ->toArray();

                $emailData['profit'] = [
                    'cost' => $routeMileCost,
                    'cash' => $routeCashCost,
                    'duration' => $routeDurationSec,
                    'stops' => $route->getStops() ?? 0,
                ];

                return $emailData;
            })
            ->filterNotNull()
            ->toArray();

        if (empty($emailData['found_routes'])) {
            return [];
        }

        $minCost = it($emailData['found_routes'])
            ->map(fn ($route) => $route['profit']['cost'])
            ->min();
        $minCash = it($emailData['found_routes'])
            ->map(fn ($route) => $route['profit']['cash'])
            ->min();
        $minDuration = it($emailData['found_routes'])
            ->map(fn ($route) => $route['profit']['duration'])
            ->min();

        $emailData['found_routes'] = it($emailData['found_routes'])
            ->map(function (array $route) use ($minCost, $minCash, $minDuration) {
                // calculate profit score
                $route['profitScore'] =
                    0.4 * ($minCost / $route['profit']['cost']) +
                    0.3 * ($minCash / $route['profit']['cash']) +
                    0.2 * ($minDuration / $route['profit']['duration']) +
                    0.1 * (1 / ($route['profit']['stops'] + 1));

                return $route;
            })
            // sort by profit score
            ->usort(fn ($a, $b) => $b['profitScore'] <=> $a['profitScore'])
            ->take(10)
            ->toArray();

        return $emailData;
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('departing', 'trips'))->setDesc('Departing'),
            (new Message('arriving', 'trips'))->setDesc('Arriving'),
            (new Message('business-class', 'trips'))->setDesc('Business'),
            (new Message('first-class', 'trips'))->setDesc('First'),
        ];
    }

    private function findGeoTag(string $aircode): ?Geotag
    {
        try {
            return $this->geoCoder->findGeoTagEntity($aircode, null, GEOTAG_TYPE_AIRPORT);
        } catch (GeoCodingFailedException $e) {
        }

        return null;
    }

    private function formatTime(\DateTime $dateTime, \DateTime $tripDepDate, string $locale): string
    {
        $formatted = $this->localizeService->formatTime($dateTime, LocalizeService::FORMAT_SHORT, $locale);

        if ($dateTime->format('Y-m-d') !== $tripDepDate->format('Y-m-d')) {
            $formatted .= ' +' . $dateTime->diff($tripDepDate)->days;
        }

        return $formatted;
    }

    /**
     * @return string like "2h 30m", "2h", "30m", without seconds
     */
    private function formatDuration(\DateTime $start, \DateTime $end, string $locale): string
    {
        $seconds = $end->getTimestamp() - $start->getTimestamp();
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $minutes %= 60;

        if ($hours > 0 && $minutes > 0) {
            return sprintf(
                '%s %s',
                $this->translator->trans('hours.short-v2', [
                    '%count%' => $hours,
                    '%number%' => $this->localizeService->formatNumber($hours),
                ], 'messages', $locale),
                $this->translator->trans('minutes.short-v2', [
                    '%count%' => $minutes,
                    '%number%' => $this->localizeService->formatNumber($minutes),
                ], 'messages', $locale)
            );
        }

        if ($hours > 0) {
            return $this->translator->trans('hours.short-v2', [
                '%count%' => $hours,
                '%number%' => $this->localizeService->formatNumber($hours),
            ], 'messages', $locale);
        }

        return $this->translator->trans('minutes.short-v2', [
            '%count%' => $minutes,
            '%number%' => $this->localizeService->formatNumber($minutes),
        ], 'messages', $locale);
    }

    private function convertCabin(?string $cabin, string $locale): ?string
    {
        switch ($cabin) {
            case RAFlightSearchQuery::API_FLIGHT_CLASS_ECONOMY:
                return $this->translator->trans('economy', [], 'messages', $locale);

            case RAFlightSearchQuery::API_FLIGHT_CLASS_PREMIUM_ECONOMY:
                return $this->translator->trans('premium-economy', [], 'messages', $locale);

            case RAFlightSearchQuery::API_FLIGHT_CLASS_BUSINESS:
                return $this->translator->trans('business-class', [], 'trips', $locale);

            case RAFlightSearchQuery::API_FLIGHT_CLASS_FIRST:
                return $this->translator->trans('first-class', [], 'trips', $locale);

            default:
                return null;
        }
    }

    private function getProviderColor(string $providerCode): ?string
    {
        switch ($providerCode) {
            case 'aeromexico':
                return '#012A5E';

            case 'aeroplan':
                return '#F01428';

            case 'alaskaair':
                return '#01426A';

            case 'asia':
                return '#005F54';

            case 'asiana':
                return '#213489';

            case 'aviancataca':
                return '#ED0707';

            case 'british':
                return '#181F57';

            case 'delta':
                return '#C01933';

            case 'etihad':
                return '#321B20';

            case 'eurobonus':
                return '#021A7E';

            case 'hawaiian':
                return '#413691';

            case 'israel':
                return '#1B358F';

            case 'jetblue':
                return '#091F58';

            case 'korean':
                return '#70B7E5';

            case 'mileageplus':
                return '#014695';

            case 'qantas':
                return '#E00201';

            case 'tapportugal':
                return '#BA141A';

            case 'turkish':
                return '#C70A0C';

            case 'velocity':
                return '#E1090A';

            case 'virgin':
                return '#60116A';
        }

        return null;
    }

    private function resolveAirline(Tripsegment $tripsegment): ?string
    {
        $tripInfo = TripInfo::createFromTripSegment($tripsegment);

        if (isset($tripInfo->primaryTripNumberInfo->companyInfo) && !empty($companyName = $tripInfo->primaryTripNumberInfo->companyInfo->companyName)) {
            return $companyName;
        }

        return null;
    }
}

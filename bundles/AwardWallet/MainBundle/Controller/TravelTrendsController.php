<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Command\TravelStatisticsCommand;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Charts\QsTransactionChart;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TravelTrendsController implements TranslationContainerInterface
{
    private $isFactor = true;

    /**
     * @Route("/travel-trends", name="aw_charts_travel_trends", options={"expose"=true}, defaults={"_canonical"="aw_charts_travel_trends_locale"})
     * @Route("/{_locale}/travel-trends", name="aw_charts_travel_trends_locale", requirements={"_locale"="%route_locales%"}, defaults={"_locale"="en", "_canonical"="aw_charts_travel_trends_locale"})
     * @Template("@AwardWalletMain/TravelTrends/travelTrends.html.twig")
     */
    public function travelTrends(
        Request $request,
        S3Client $s3Client,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        SessionInterface $session,
        PageVisitLogger $pageVisitLogger
    ) {
        $cacheData = $this->getTravelStatisticCacheData($request, $s3Client, $authorizationChecker, $session);
        /** @var Usr $user */
        $user = $tokenStorage->getToken()->getUser() instanceof Usr ? $tokenStorage->getToken()->getUser() : null;

        $templateParams = [
            'user' => $user,
            'noAvailable' => empty($cacheData),
            'isSiteAdmin' => $user ? $user->isSiteAdmin() : false,
            'debugData' => true === $request->query->getBoolean('debug') && false !== $user && $user->isSiteAdmin() ? $this->debugData($cacheData) : '',
            'suffix' => !empty($cacheData) ? $cacheData['suffix'] : null,
        ];

        if ($templateParams['isSiteAdmin'] && true === $request->query->getBoolean('dataExport')) {
            exit('<pre>' . var_export($cacheData, true));
        }

        if (!empty($cacheData)) {
            $templateParams = $this->topTravel($cacheData, $templateParams);
            $templateParams['topFlightRoutes'] = $this->topFlightRoutes($cacheData);
        }
        $pageVisitLogger->log(PageVisitLogger::PAGE_TRAVEL_TRENDS);

        return $templateParams;
    }

    /**
     * @Route("/travel-trends/number-reservation/data", methods={"POST"}, name="aw_charts_travel_trends_numberReservation_data", options={"expose"=true})
     */
    public function numberReservationData(Request $request, S3Client $s3Client, TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker, SessionInterface $session)
    {
        $cacheData = $this->getTravelStatisticCacheData($request, $s3Client, $authorizationChecker, $session);

        if (empty($cacheData)) {
            return new JsonResponse([]);
        }
        $user = $tokenStorage->getToken()->getUser() instanceof Usr ? $tokenStorage->getToken()->getUser() : null;

        $allowDateType = [
            'day' => TravelStatisticsCommand::PERIOD_DAY,
            'month' => TravelStatisticsCommand::PERIOD_MONTH,
        ];
        $allowProviderType = [
            'flights' => TravelStatisticsCommand::TYPE_FLIGHTS,
            'hotels' => TravelStatisticsCommand::TYPE_HOTELS,
            'rentedCars' => TravelStatisticsCommand::TYPE_RENTED_CARS,
        ];

        $dateType = $request->get('dateType', $allowDateType['month']);

        if (!in_array($dateType, $allowDateType)) {
            $dateType = $allowDateType['month'];
        }
        $checkedType = $request->request->get('type');
        $providerType = $request->get('providerType');

        if (!in_array($providerType, $allowProviderType)) {
            $providerType = empty($providerType) ? null : $allowProviderType['flights'];
        }

        $isDaily = ($dateType === $allowDateType['day']);

        $labels = $dataset = [];
        $divider = $cacheData['usersCount']['all'];
        $usersCount = $isDaily ? $cacheData['usersCount'][TravelStatisticsCommand::PERIOD_DAY] : $cacheData['usersCount'][TravelStatisticsCommand::PERIOD_MONTH];
        $factor = $isDaily ? 10000 : 1000;

        $datesPeriod = $this->fetchDatePeriods($isDaily, true);
        $dateFormat = $isDaily ? 'Y-m-d' : 'Y-m';
        $keyData = $isDaily ? $allowDateType['day'] : $allowDateType['month'];

        foreach ($datesPeriod as $date) {
            $labels[] = $date->format('Y-m-d');
        }

        if (null !== $providerType && $user && $user->isAwPlus()) {
            if (TravelStatisticsCommand::TYPE_FLIGHTS === $providerType) {
                $providers = TravelStatisticsCommand::FLIGHTS_OPERATING_AIRLINE_ID;
            } elseif (TravelStatisticsCommand::TYPE_HOTELS === $providerType) {
                $providers = TravelStatisticsCommand::HOTELS_PROVIDER_ID;
            } elseif (TravelStatisticsCommand::TYPE_RENTED_CARS === $providerType) {
                $providers = TravelStatisticsCommand::RENTED_CARS_PROVIDER_ID;
            } else {
                throw new \Exception('Unknown type');
            }

            $i = -1;
            $index = 0;
            $datasetPersist = [];

            $valuesDateSet = [];

            foreach ($datesPeriod as $date) {
                $valuesDateSet[$date->format($dateFormat)] = 0;
            }

            $checkedList = $request->request->get('providersList');

            if (!empty($checkedList)) {
                $checkedList = array_map('intval', str_split($checkedList, 1));

                if (0 === array_sum($checkedList)) {
                    $checkedList[0] = 1;
                }
            } else {
                $checkedList = [1];
            }

            foreach ($cacheData['provider'][$keyData][$providerType] as $id => $values) {
                if (empty($providers[$id])) {
                    continue;
                }

                if (isset(QsTransactionChart::COLORS_LIST[++$i])) {
                    $color = QsTransactionChart::COLORS_LIST[$i];
                } else {
                    $color = QsTransactionChart::COLORS_LIST[0];
                    $i = -1;
                }

                $_usersCount = $usersCount;

                if ('countryall' === $cacheData['suffix']) {
                    if (in_array($id, [39, 243, 676, 98, 404, 740])) {
                        $_usersCount = $cacheData['usersCount']['country']['us'];
                    } elseif (in_array($id, [129])) {
                        $_usersCount = $cacheData['usersCount']['country']['gb'];
                    } elseif (in_array($id, [254])) {
                        $_usersCount = $cacheData['usersCount']['country']['de'];
                    } elseif (in_array($id, [739])) {
                        $_usersCount = $cacheData['usersCount']['country']['ae'];
                    }
                    $_usersCount = $isDaily ? $_usersCount[TravelStatisticsCommand::PERIOD_DAY] : $_usersCount[TravelStatisticsCommand::PERIOD_MONTH];
                }

                $factorValues = $this->factorDataset($values, $_usersCount, $factor);
                $_valuesDateSet = $valuesDateSet;

                foreach ($datesPeriod as $date) {
                    $keyDate = $date->format($dateFormat);
                    $_valuesDateSet[$keyDate] = array_key_exists($keyDate, $factorValues) ? $factorValues[$keyDate] : 0;
                }

                $_data = [
                    'label' => $providers[$id],
                    'backgroundColor' => $color,
                    'borderWidth' => 0,
                    'data' => array_values($_valuesDateSet),
                    'hidden' => isset($checkedList[$index]) && 1 === $checkedList[$index] ? false : true,
                ];
                ++$index;
                $datasetPersist[] = $_data;

                if (array_sum($factorValues)) {
                    $dataset[] = $_data;
                }
            }

            if (empty($dataset)) {
                $dataset = $datasetPersist;
            }
        } else {
            $dataset = [
                [
                    'label' => /** @Ignore */
                        'Flights',
                    'backgroundColor' => '#4285f4',
                    'borderWidth' => 0,
                    'data' => array_values($this->factorDataset($this->getDatePeriodSlice($isDaily, $cacheData['type'][$keyData]['flights'], true), $usersCount, $factor)),
                    'hidden' => 'false' === ($checkedType['flights'] ?? null),
                    'datalabels' => [
                        'align' => 'center',
                        'anchor' => 'center',
                    ],
                ],
                [
                    'label' => /** @Ignore */
                        'Hotels',
                    'backgroundColor' => '#ea3c48',
                    'borderWidth' => 0,
                    'data' => array_values($this->factorDataset($this->getDatePeriodSlice($isDaily, $cacheData['type'][$keyData]['hotels'], true), $usersCount, $factor)),
                    'hidden' => 'false' === ($checkedType['hotels'] ?? null),
                ],
                [
                    'label' => /** @Ignore */
                        'Rented Cars',
                    'backgroundColor' => '#30d178',
                    'borderWidth' => 0,
                    'data' => array_values($this->factorDataset($this->getDatePeriodSlice($isDaily, $cacheData['type'][$keyData]['rentedCars'], true), $usersCount, $factor)),
                    'hidden' => 'false' === ($checkedType['rentedCars'] ?? null),
                ],
            ];
        }

        $response = [
            'chart' => [
                'data' => [
                    'labels' => $labels,
                    'datasets' => $dataset,
                ],
            ],
            'isAuth' => $authorizationChecker->isGranted('ROLE_USER'),
            'isAwPlus' => $user ? $user->isAwPlus() : false,
        ];

        return new JsonResponse($response);
    }

    /**
     * @Route("/travel-trends/long-haul/data", methods={"POST"}, name="aw_charts_travel_trends_longHaul_data", options={"expose"=true})
     */
    public function longHaulData(Request $request, S3Client $s3Client, LocalizeService $localizeService, AuthorizationCheckerInterface $authorizationChecker, SessionInterface $session)
    {
        $cacheData = $this->getTravelStatisticCacheData($request, $s3Client, $authorizationChecker, $session);

        if (empty($cacheData)) {
            return new JsonResponse([]);
        }

        $longhaulData = $cacheData[TravelStatisticsCommand::TYPE_LONGHAUL][TravelStatisticsCommand::PERIOD_MONTH]['flights'];
        $dateType = TravelStatisticsCommand::PERIOD_MONTH; // $request->get('dateType', TravelStatisticsCommand::PERIOD_MONTH);
        $isDaily = TravelStatisticsCommand::PERIOD_DAY === $dateType;
        $datesPeriod = $this->fetchDatePeriods($isDaily);
        $dateFormat = $isDaily ? 'Y-m-d' : 'Y-m';
        $usersCount = $isDaily ? $cacheData['usersCount'][TravelStatisticsCommand::PERIOD_DAY] : $cacheData['usersCount'][TravelStatisticsCommand::PERIOD_MONTH];
        $factor = $isDaily ? 10000 : 1000;

        $labels = $shorts = $longs = [];

        foreach ($datesPeriod as $date) {
            $labels[] = $date->format('Y-m-d');
            $keyDate = $date->format($dateFormat);

            if (array_key_exists($keyDate, $longhaulData)) {
                $shorts[$keyDate] = $longhaulData[$keyDate]['short'];
                $longs[$keyDate] = $longhaulData[$keyDate]['long'];

                if (0 !== $longhaulData[$keyDate]['diff']) {
                    $shorts[$keyDate] += $longhaulData[$keyDate]['diff'];
                }
            } else {
                $shorts[$keyDate] = 0;
                $longs[$keyDate] = 0;
            }
        }

        $dataset = [
            [
                'label' => /** @Ignore */
                    'Short Haul',
                // 'stack' => 'stack',
                'backgroundColor' => QsTransactionChart::COLORS_LIST[5],
                'data' => array_values($this->factorDataset($shorts, $usersCount, $factor)),
                'hidden' => 'false' === $request->request->get('short', 'true'),
            ],
            [
                'label' => /** @Ignore */
                    'Long Haul',
                // 'stack' => 'stack',
                'backgroundColor' => QsTransactionChart::COLORS_LIST[9],
                'data' => array_values($this->factorDataset($longs, $usersCount, $factor)),
                'hidden' => 'false' === $request->request->get('long', 'true'),
            ],
        ];

        $response = [
            'chart' => [
                'data' => [
                    'labels' => $labels,
                    'datasets' => $dataset,
                ],
            ],
        ];

        return new JsonResponse($response);
    }

    /**
     * @Route("/travel-trends/earning-redemption/data", methods={"POST"}, name="aw_charts_travel_trends_earningRedemption_data", options={"expose"=true})
     */
    public function earningRedemptionData(Request $request, S3Client $s3Client, LocalizeService $localizeService, AuthorizationCheckerInterface $authorizationChecker, SessionInterface $session)
    {
        $cacheData = $this->getTravelStatisticCacheData($request, $s3Client, $authorizationChecker, $session);

        if (empty($cacheData)) {
            return new JsonResponse([]);
        }

        $type = $request->request->get('type', TravelStatisticsCommand::TYPE_TOTAL_BANKS);

        if (!in_array($type, [TravelStatisticsCommand::TYPE_TOTAL_BANKS, TravelStatisticsCommand::TYPE_TOTAL_HOTELS, TravelStatisticsCommand::TYPE_TOTAL_AIRLINES])) {
            $type = TravelStatisticsCommand::TYPE_TOTAL_BANKS;
        }

        $epData = $cacheData[TravelStatisticsCommand::TOTALLY_EARNING_MP_DATA_KEY][TravelStatisticsCommand::PERIOD_MONTH];
        $count = TravelStatisticsCommand::PERIOD_MONTH_COUNT;
        $labels = $banks = $hotels = $airlines = [];
        $zero = ['earnings' => 0, 'redemptions' => 0];

        for ($i = 0; $i <= $count; $i++) {
            $date = new \DateTimeImmutable('@' . strtotime(date('Y-m-01') . " -" . ($count - $i) . " months"));

            $labels[] = $date->format('Y-m-d');
            $keyDate = $date->format('Y-m');

            $banks[] = array_key_exists($keyDate, $epData['banks']) ? $epData['banks'][$keyDate] : $zero;
            $hotels[] = array_key_exists($keyDate, $epData['hotels']) ? $epData['hotels'][$keyDate] : $zero;
            $airlines[] = array_key_exists($keyDate, $epData['airlines']) ? $epData['airlines'][$keyDate] : $zero;
        }

        $bignumberFormat = function (array $values) use ($localizeService): array {
            $result = [];

            foreach ($values as $value) {
                $result[] = $localizeService->formatNumberShort(abs($value), 0);
            }

            return $result;
        };

        $dataset = [
            [
                'label' => /** @Ignore */
                    'Banks Earnings',
                'backgroundColor' => '#109617',
                'data' => array_column($banks, 'earnings'),
                'dataLabel' => $bignumberFormat(array_column($banks, 'earnings')),
                'hidden' => !(TravelStatisticsCommand::TYPE_TOTAL_BANKS === $type),
            ],
            [
                'label' => /** @Ignore */
                    'Banks Redemptions',
                'backgroundColor' => QsTransactionChart::COLORS_LIST[9],
                'data' => array_map('abs', array_column($banks, 'redemptions')),
                'dataLabel' => $bignumberFormat(array_column($banks, 'redemptions')),
                'hidden' => !(TravelStatisticsCommand::TYPE_TOTAL_BANKS === $type),
            ],

            [
                'label' => /** @Ignore */
                    'Hotels Earnings',
                'backgroundColor' => '#109617',
                'data' => array_column($hotels, 'earnings'),
                'dataLabel' => $bignumberFormat(array_column($hotels, 'earnings')),
                'hidden' => !(TravelStatisticsCommand::TYPE_TOTAL_HOTELS === $type),
            ],
            [
                'label' => /** @Ignore */
                    'Hotels Redemptions',
                'backgroundColor' => QsTransactionChart::COLORS_LIST[9],
                'data' => array_map('abs', array_column($hotels, 'redemptions')),
                'dataLabel' => $bignumberFormat(array_column($hotels, 'redemptions')),
                'hidden' => !(TravelStatisticsCommand::TYPE_TOTAL_HOTELS === $type),
            ],

            [
                'label' => /** @Ignore */
                    'Airlines Earnings',
                'backgroundColor' => '#109617',
                'data' => array_column($airlines, 'earnings'),
                'dataLabel' => $bignumberFormat(array_column($airlines, 'earnings')),
                'hidden' => !(TravelStatisticsCommand::TYPE_TOTAL_AIRLINES === $type),
            ],
            [
                'label' => /** @Ignore */
                    'Airlines Redemptions',
                'backgroundColor' => QsTransactionChart::COLORS_LIST[9],
                'data' => array_map('abs', array_column($airlines, 'redemptions')),
                'dataLabel' => $bignumberFormat(array_column($airlines, 'redemptions')),
                'hidden' => !(TravelStatisticsCommand::TYPE_TOTAL_AIRLINES === $type),
            ],
        ];

        $response = [
            'chart' => [
                'data' => [
                    'labels' => $labels,
                    'datasets' => $dataset,
                ],
            ],
        ];

        return new JsonResponse($response);
    }

    /**
     * @Route("/travel-trends/cancelled/data", name="aw_charts_travel_trends_cancelled_data", options={"expose"=true})
     */
    public function cancelledData(Request $request, S3Client $s3Client, LocalizeService $localizeService, AuthorizationCheckerInterface $authorizationChecker, SessionInterface $session)
    {
        $cacheData = $this->getTravelStatisticCacheData($request, $s3Client, $authorizationChecker, $session);

        if (empty($cacheData)) {
            return new JsonResponse([]);
        }

        $isDaily = false;
        $checkedType = $request->request->get('type');
        $allowCheckedType = ['flights' => 'false', 'hotels' => 'true', 'rentedCars' => 'true'];

        if (!is_array($checkedType)) {
            $checkedType = $allowCheckedType;
        }
        $checkedType = array_merge($allowCheckedType, $checkedType);
        $labels = $dataset = [];
        $divider = $cacheData['usersCount']['all'];
        $usersCount = $isDaily ? $cacheData['usersCount'][TravelStatisticsCommand::PERIOD_DAY] : $cacheData['usersCount'][TravelStatisticsCommand::PERIOD_MONTH];
        $factor = $isDaily ? 10000 : 1000;

        $datesPeriod = $this->fetchDatePeriods($isDaily, true);
        $dateFormat = $isDaily ? 'Y-m-d' : 'Y-m';
        $keyData = $isDaily ? TravelStatisticsCommand::PERIOD_DAY : TravelStatisticsCommand::PERIOD_MONTH;

        foreach ($datesPeriod as $date) {
            $labels[] = $date->format('Y-m-d');
        }

        $dataset = [
            [
                'label' => /** @Ignore */
                    'Flights',
                'backgroundColor' => '#4285f4',
                'borderWidth' => 0,
                'data' => array_values($this->factorDataset($this->getDatePeriodSlice($isDaily, $cacheData[TravelStatisticsCommand::CANCELLED_DATA_KEY][$keyData]['flights'], true), $usersCount, $factor)),
                'hidden' => 'false' === $checkedType['flights'],
                'datalabels' => [
                    'align' => 'center',
                    'anchor' => 'center',
                ],
            ],
            [
                'label' => /** @Ignore */
                    'Hotels',
                'backgroundColor' => '#ea3c48',
                'borderWidth' => 0,
                'data' => array_values($this->factorDataset($this->getDatePeriodSlice($isDaily, $cacheData[TravelStatisticsCommand::CANCELLED_DATA_KEY][$keyData]['hotels'], true), $usersCount, $factor)),
                'hidden' => 'false' === $checkedType['hotels'],
            ],
            [
                'label' => /** @Ignore */
                    'Rented Cars',
                'backgroundColor' => '#30d178',
                'borderWidth' => 0,
                'data' => array_values($this->factorDataset($this->getDatePeriodSlice($isDaily, $cacheData[TravelStatisticsCommand::CANCELLED_DATA_KEY][$keyData]['rentedCars'], true), $usersCount, $factor)),
                'hidden' => 'false' === $checkedType['rentedCars'],
            ],
        ];

        $response = [
            'chart' => [
                'data' => [
                    'labels' => $labels,
                    'datasets' => $dataset,
                ],
            ],
        ];

        return new JsonResponse($response);
    }

    /**
     * @return array<Message>
     */
    public static function getTranslationMessages(): array
    {
        return [
            (new Message('travel-trends'))->setDesc('Travel Trends'),
            (new Message('travel-statistics'))->setDesc('Travel Statistics'),
            (new Message('by-month'))->setDesc('By Month'),
            (new Message('by-day'))->setDesc('By Day'),
            (new Message('by-type'))->setDesc('By Type'),
            (new Message('air-trips'))->setDesc('Air Trips'),
            (new Message('hotel-stays'))->setDesc('Hotel Stays'),
            (new Message('car-rentals'))->setDesc('Car Rentals'),
            (new Message('by-provider'))->setDesc('By Provider'),
            (new Message('air-carriers'))->setDesc('Air Carriers'),
            (new Message('hotel-chains'))->setDesc('Hotel Chains'),
            (new Message('car-rentals'))->setDesc('Car Rentals'),
            (new Message('air-tickets-cancelled'))->setDesc('Air tickets canceled'),
            (new Message('hotel-bookings-cancelled'))->setDesc('Hotel bookings canceled'),
            (new Message('rental-cars-cancelled'))->setDesc('Rental cars canceled'),
            (new Message('data-based-user.reference-aw-direct-link'))->setDesc('This data is based on the AwardWallet user base. If you wish to reference it, please always make attribution back to AwardWallet with a direct link to this page as follows:
                            %break%%bold_on%This chart [or data] was provided by %link_on%AwardWallet, a service for tracking frequent flyer miles and loyalty points%link_off%.%bold_off%'),
            (new Message('long-haul'))->setDesc('Long Haul'),
            (new Message('short-haul'))->setDesc('Short Haul'),
            (new Message('stack'))->setDesc('Stack'),
            (new Message('banks'))->setDesc('Banks'),
            (new Message('hotels-top-travel-destinations'))->setDesc('Hotels - Top Travel Destinations by Year'),
            (new Message('year'))->setDesc('Year'),
            (new Message('by-continent'))->setDesc('By Continent'),
            (new Message('by-country'))->setDesc('By Country'),
            (new Message('rental-cars-top-travel-destinations'))->setDesc('Rental Cars - Top Travel Destinations by Year'),
            (new Message('top-routes-by-year.v2'))->setDesc('Top %number% Route by Year|Top %number% Routes by Year'),
            (new Message('must-have-awplus.login-see-data'))->setDesc('You must have an AwardWallet Plus account to see this data, please login to AwardWallet and refresh this page'),
            (new Message('must-have-awplus.upgrade-see-data'))->setDesc('You must have an AwardWallet Plus account to see this data, please upgrade your account'),
            (new Message('awplus-is-required'))->setDesc('AwardWallet Plus Is Required'),
            (new Message('avg-number-reservations-per-users.v2'))->setDesc('Average Number of Reservations Taken per %number% User|Average Number of Reservations Taken per %number% Users'),
            (new Message('avg-number-cancellations.v2'))->setDesc('Average Number of Cancellations Made per %number% User|Average Number of Cancellations Made per %number% Users'),
            (new Message('longshort-haul-flights-per-users'))->setDesc('Long Haul vs Short Haul Flights Taken per %number% Users'),
            (new Message('mile-point-earning-redemption'))->setDesc('Mile and Point Earnings vs Redemptions'),
        ];
    }

    private function topTravel(array $cacheData, array $templateParams): array
    {
        if (!array_key_exists(TravelStatisticsCommand::CONTINENT_COUNTRY_DATA_KEY, $cacheData)) {
            return [];
        }

        $recentCount = 0 - TravelStatisticsCommand::TOP_YEAR_COUNT;
        $templateParams['earningRedemptions'] = [
            'banksList' => [], // TravelStatisticsCommand::TOTALLY_BANKS_PROVIDER_ID,
            'hotelsList' => TravelStatisticsCommand::TOTALLY_HOTELS_PROVIDER_ID,
            'airlinesList' => TravelStatisticsCommand::TOTALLY_AIRLINES_PROVIDER_ID,
        ];

        $templateParams['continents'] = $cacheData[TravelStatisticsCommand::CONTINENT_COUNTRY_DATA_KEY];
        $templateParams['countryByCode'] = $cacheData[TravelStatisticsCommand::COUNTRY_BY_CODE_DATA_KEY];

        $templateParams['topYears'] = array_reverse(array_slice(array_keys($cacheData[TravelStatisticsCommand::TOP_HOTELS_DATA_KEY][0]), $recentCount));

        $templateParams['topHotels'] = $this->normalizeTopsData($cacheData[TravelStatisticsCommand::TOP_HOTELS_DATA_KEY], 'totalReservations', $recentCount, $templateParams);
        $templateParams['topRentedCars'] = $this->normalizeTopsData($cacheData[TravelStatisticsCommand::TOP_RENTEDCARS_DATA_KEY], 'totalRentals', $recentCount, $templateParams);

        return $templateParams;
    }

    private function topFlightRoutes(array $cacheData): array
    {
        if (!array_key_exists(TravelStatisticsCommand::TOP_FLIGHT_ROUTES_DATA_KEY, $cacheData)) {
            return [];
        }

        $recentCount = 0 - TravelStatisticsCommand::TOP_YEAR_COUNT;
        $topFlightRoutes = $cacheData[TravelStatisticsCommand::TOP_FLIGHT_ROUTES_DATA_KEY];

        $topFlightRoutes = array_slice($topFlightRoutes, $recentCount, null, true);
        $topFlightRoutes = array_reverse($topFlightRoutes, true);

        $aircodes = $cacheData[TravelStatisticsCommand::AIRCODE_DATA_KEY];
        $sum = [];

        $limit = 10;
        $index = 0;

        foreach ($topFlightRoutes as $year => &$data) {
            if (++$index > $limit) {
                break;
            }
            $sum[$year] = [
                'long' => array_sum(array_slice(array_column($data['long'], 'count'), 0, $limit)) + array_sum(array_slice(array_column($data['long'], 'reverseCount'), 0, $limit)),
                'short' => array_sum(array_slice(array_column($data['short'], 'count'), 0, $limit)) + array_sum(array_slice(array_column($data['short'], 'reverseCount'), 0, $limit)),
            ];
            $data['long'] = array_slice($data['long'], 0, $limit);

            foreach ($data['long'] as $routes => $items) {
                [$dep, $arr] = explode('-', $routes);

                if (array_key_exists($dep, $aircodes)) {
                    $data['long'][$routes]['dep'] = $aircodes[$dep];
                }

                if (array_key_exists($arr, $aircodes)) {
                    $data['long'][$routes]['arr'] = $aircodes[$arr];
                }
                $data['long'][$routes]['percent'] = $this->calcPercent($sum[$year]['long'], $items['count'] + $items['reverseCount']);
            }
            $data['short'] = array_slice($data['short'], 0, $limit);

            foreach ($data['short'] as $routes => $items) {
                [$dep, $arr] = explode('-', $routes);

                if (array_key_exists($dep, $aircodes)) {
                    $data['short'][$routes]['dep'] = $aircodes[$dep];
                }

                if (array_key_exists($arr, $aircodes)) {
                    $data['short'][$routes]['arr'] = $aircodes[$arr];
                }
                $data['short'][$routes]['percent'] = $this->calcPercent($sum[$year]['short'], $items['count'] + $items['reverseCount']);
            }
        }

        foreach ($topFlightRoutes as $year => &$data) {
            uksort($data['long'],
                function ($a, $b) use ($data) {
                    return $data['long'][$b]['percent'] > $data['long'][$a]['percent'] ? 1 : 0;
                });
            uksort($data['short'],
                function ($a, $b) use ($data) {
                    return $data['short'][$b]['percent'] > $data['short'][$a]['percent'] ? 1 : 0;
                });
        }

        return $topFlightRoutes;
    }

    private function normalizeTopsData(array $topDatas, string $field, int $recentCount, array $templateParams): array
    {
        $limit = 10;

        foreach ($topDatas as $continentId => &$dataByYears) {
            $dataByYears = array_slice($dataByYears, $recentCount, null, true);
            $dataByYears = array_reverse($dataByYears, true);

            foreach ($dataByYears as $year => $data) {
                foreach ($data['top'] as $key => $item) {
                    $dataByYears[$year]['top'][$key]['totalsByCountry'] = array_key_exists($item['countryCode'], $data['totalsByCountry']) ? $data['totalsByCountry'][$item['countryCode']][$field] : 'n/a';
                    $dataByYears[$year]['top'][$key]['countryName'] = array_key_exists($item['countryCode'], $templateParams['countryByCode']) ? $templateParams['countryByCode'][$item['countryCode']]['Name'] : $item['countryCode'];
                }

                $dataByYears[$year]['insideCountryList'] = [];

                foreach ($data['insideCountry'] as $countryCode => $item) {
                    $dataByYears[$year]['insideCountry'][$countryCode] = array_slice($dataByYears[$year]['insideCountry'][$countryCode], 0, $limit);

                    $totalsByCountry = array_key_exists($countryCode, $data['totalsByCountry']) ? $data['totalsByCountry'][$countryCode][$field] : 0;

                    if ($totalsByCountry >= 100) {
                        $dataByYears[$year]['insideCountryList'][$countryCode]['countryCode'] = $countryCode;
                        $dataByYears[$year]['insideCountryList'][$countryCode]['totalsByCountry'] = $totalsByCountry;
                        $dataByYears[$year]['insideCountryList'][$countryCode]['countryName'] = array_key_exists($countryCode, $templateParams['countryByCode']) ? $templateParams['countryByCode'][$countryCode]['Name'] : $countryCode;
                    }
                }

                usort($dataByYears[$year]['insideCountryList'],
                    function ($a, $b) {
                        return $b['totalsByCountry'] - $a['totalsByCountry'];
                    });
            }
        }

        $citysList = [];

        foreach ($topDatas as $continentId => $dataByYears2) {
            foreach ($dataByYears2 as $year => $data) {
                $citysList[$year][$continentId] = $dataByYears2[$year]['insideCountryList'];
            }
        }

        foreach ($topDatas as $continentId => &$dataByYears3) {
            foreach ($dataByYears3 as $year => $data) {
                $dataByYears3[$year]['citys'] = $citysList[$year];
            }
        }

        // ---

        foreach ($topDatas as $continentId => &$dataByYears) {
            foreach ($dataByYears as $year => $data) {
                $dataByYears[$year]['top'] = array_slice($data['top'], 0, $limit);
                $sum = array_sum(array_column($dataByYears[$year]['top'], 'count'));

                foreach ($dataByYears[$year]['top'] as $key => $item) {
                    $dataByYears[$year]['top'][$key]['countByPercent'] = $this->calcPercent($sum, $dataByYears[$year]['top'][$key]['count']);
                }

                $insideList = [];
                $sum = 0;

                foreach ($dataByYears[$year]['insideCountryList'] as $item) {
                    if ($item['totalsByCountry'] >= 100) {
                        $insideList[] = $item;
                        $sum += $item['totalsByCountry'];
                    }
                }

                foreach ($insideList as &$item) {
                    $item['totalsByPercent'] = $this->calcPercent($sum, $item['totalsByCountry']);
                }
                $dataByYears[$year]['insideCountryList'] = $insideList;

                foreach ($data['insideCountry'] as $countryCode => $items) {
                    $sum = array_sum(array_column($items, 'count'));

                    foreach ($items as $key => $item) {
                        $dataByYears[$year]['insideCountry'][$countryCode][$key]['countByPercent'] = $this->calcPercent($sum, $item['count']);
                    }
                }

                foreach ($dataByYears[$year]['citys'] as $key => $items) {
                    $sum = array_sum(array_column($items, 'totalsByCountry'));

                    foreach ($items as $k => $item) {
                        $dataByYears[$year]['citys'][$key][$k]['totalsByPercent'] = $this->calcPercent($sum, $item['totalsByCountry']);
                    }
                }
            }
        }

        return $topDatas;
    }

    private function getDatePeriodSlice(bool $isDaily, array $data, bool $withFuture = false)
    {
        $datesPeriod = $this->fetchDatePeriods($isDaily, $withFuture);
        $dateFormat = $isDaily ? 'Y-m-d' : 'Y-m';
        $result = [];

        foreach ($datesPeriod as $date) {
            $keyDate = $date->format($dateFormat);
            $result[$keyDate] = array_key_exists($keyDate, $data) ? $data[$keyDate] : 0;
        }

        return $result;
    }

    private function fetchDatePeriods(bool $isDaily, bool $withFuture = false): array
    {
        $period = [];
        $date = new \DateTimeImmutable();
        $date->setTime(0, 0, 0);

        if ($isDaily) {
            $count = TravelStatisticsCommand::PERIOD_DAY_COUNT;
            $date->modify('-' . $count . ' days');

            for ($i = $count; $i >= 0; $i--) {
                $period[] = $date->modify('-' . $i . ' day');
            }

            return $period;
        }

        $count = TravelStatisticsCommand::PERIOD_MONTH_COUNT;

        if (!$isDaily && $withFuture) {
            $count -= TravelStatisticsCommand::PERIOD_MONTH_FUTURE;
        }
        $date->modify('-' . $count . ' months');

        for ($i = $count; $i >= 0; $i--) {
            $period[] = new \DateTimeImmutable('@' . strtotime(date('Y-m-01') . " -$i months"));
        }

        if (!$isDaily && $withFuture) {
            for ($i = 1; $i <= TravelStatisticsCommand::PERIOD_MONTH_FUTURE; $i++) {
                $period[] = new \DateTimeImmutable('@' . strtotime(date('Y-m-01') . " +$i months"));
            }
        }

        return $period;
    }

    private function getTravelStatisticCacheData(Request $request, S3Client $s3Client, AuthorizationCheckerInterface $authorizationChecker, SessionInterface $session): ?array
    {
        if ($authorizationChecker->isGranted('ROLE_STAFF')) {
            $suffix = $request->query->get('suffix');

            if (!empty($suffix)) {
                $session->set('travelStatisticSuffix', $suffix);
            }
        } else {
            $suffix = 'countryus';
        }

        if (null !== $suffix) {
            $session->set('travelStatisticSuffix', $suffix);
        }
        $isFactor = $request->query->get('factor');

        if (null !== $isFactor) {
            $session->set('isFactor', !empty($isFactor));
        }
        $this->isFactor = true; // $session->has('isFactor') ? $session->get('isFactor') : true;

        if (isset($session)) {
            $suffix = $session->get('travelStatisticSuffix');
        }
        $suffix = 'debug' === $suffix && $this->isGranted('ROLE_STAFF') ? 'debug' : 'countryus';

        try {
            $cacheData = $s3Client->getObject(
                [
                    'Bucket' => TravelStatisticsCommand::BUCKET,
                    'Key' => TravelStatisticsCommand::CACHE_KEY . ':' . $suffix,
                ]
            );
        } catch (S3Exception $exception) {
            return null;
        }

        $cacheData = unserialize($cacheData['Body']);

        if (empty($cacheData)) {
            return null;
        }

        $cacheData['suffix'] = $suffix;

        return $cacheData;
    }

    private function factorDataset(array $listValues, array $usersCount, int $factor): array
    {
        $values = [];
        $last = $usersCount[array_key_last($usersCount)];

        foreach ($listValues as $date => $value) {
            if (array_key_exists($date, $usersCount)) {
                $usersCountFactor = $usersCount[$date];
            } else {
                $prevDate = new \DateTime($date);

                if (7 === strlen($date)) {
                    $prevDate->modify('-1 month');
                    $usersCountFactor = $usersCount[$prevDate->format('Y-m')] ?? $last;
                } else {
                    $prevDate->modify('-1 day');
                    $usersCountFactor = $usersCount[$prevDate->format('Y-m-d')];
                }
            }
            $values[$date] = $this->isFactor ? round(($value / $usersCountFactor) * $factor, 0) : $value;
        }

        return $values;
    }

    private function calcPercent(int $sum, int $value): float
    {
        return round($value / $sum * 100, 1);
    }

    private function debugData(array $cacheData)
    {
        $html = '<hr>';
        $html .= '<h1>By Type</h1>';

        foreach ($cacheData['type'] as $dateKey => $items) {
            $html .= '<table class="main-table no-border hover-style transfer-times" style="float:left;margin: 0 1rem;">';
            $html .= '<tr><td colspan="4" style="text-align: center"><h2>' . $dateKey . '</h2></td></tr>';
            $html .= '<tr>';
            $html .= '<th>date</th>';

            foreach ($items as $reservationType => $listDates) {
                $html .= '<th>' . $reservationType . '</th>';
            }
            $html .= '</tr>';

            foreach ($items as $reservationType => $listDates) {
                foreach ($listDates as $key => $count) {
                    $html .= '<tr>';

                    $html .= '<td>' . $key . '</td>';
                    $html .= '<td>' . $items['flights'][$key] . '</td>';
                    $html .= '<td>' . $items['hotels'][$key] . '</td>';
                    $html .= '<td>' . ($items['rentedCars'][$key] ?? '--') . '</td>';

                    $html .= '</tr>';
                }
            }
            $html .= '</table>';
        }
        $html .= '<br clear="all"><br><hr><br clear="all">';

        $html .= '<h1>By Provider</h1>';
        $html .= '<table class="main-table no-border hover-style transfer-times">';

        foreach ($cacheData['provider'] as $dateKey => $items) {
            foreach ($items as $type => $providers) {
                if ('flights' === $type) {
                    $providersName = TravelStatisticsCommand::FLIGHTS_OPERATING_AIRLINE_ID;
                } elseif ('hotels' === $type) {
                    $providersName = TravelStatisticsCommand::HOTELS_PROVIDER_ID;
                } else {
                    $providersName = TravelStatisticsCommand::RENTED_CARS_PROVIDER_ID;
                }

                if (empty($providers)) {
                    continue;
                }

                $dates = array_values($providers)[0];
                $html .= '<tr style="background-color: #ddd">';
                $html .= '<th><h2>' . $type . ' &mdash; ' . $dateKey . '</h2></th>';

                foreach ($dates as $date => $count) {
                    $html .= '<td>' . $date . '</td>';
                }
                $html .= '</tr>';

                foreach ($providers as $id => $listDates) {
                    if (empty($providersName[$id])) {
                        continue;
                    }
                    $html .= '<tr>';
                    $html .= '<th>' . $providersName[$id] . '</th>';

                    foreach ($listDates as $date => $count) {
                        $html .= '<td>' . $count . '</td>';
                    }
                    $html .= '</tr>';
                }
            }
        }

        $html .= '</table>';

        $html .= '<br><br><hr><br>';
        $html .= '<h1>Users Count</h1>';
        $html .= '<table class="main-table no-border hover-style transfer-times">';

        foreach ($cacheData['usersCount'] as $periodType => $dates) {
            $html .= '<tr><td><h2>' . $periodType . '</h2></td></tr>';

            if (!is_array($dates)) {
                $html .= '<tr>';
                $html .= '<th style="text-align: left">' . $dates . '</th>';
                $html .= '</tr>';
            } else {
                $html .= '<tr>';
                $html .= '<th><table><tr>';

                foreach ($dates as $d => $val) {
                    $html .= '<td>' . $d . '<br>' . $val . '</td>';
                }
                $html .= '</tr></table></th>';
                $html .= '</tr>';
            }
        }
        $html .= '</table>';

        return $html;
    }
}

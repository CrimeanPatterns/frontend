<?php

namespace AwardWallet\MainBundle\Controller\Manager\CreditCards;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\DesktopListMapper;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Service\AmericanAirlinesAAdvantageDetector;
use AwardWallet\MainBundle\Service\CreditCards\UserSpending;
use AwardWallet\MainBundle\Service\ProviderHandler;
use AwardWallet\MainBundle\Service\Quinstreet\UpdateQsTransactionQmpCommand;
use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Manager;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/manager/credit-card/suggestion-tool")
 */
class SuggestionToolController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private Manager $timelineManager;
    private OptionsFactory $optionsFactory;
    private AccountListManager $accountListManager;
    private AwTokenStorageInterface $tokenStorage;
    private DesktopListMapper $desktopListMapper;
    private ProviderHandler $providerHandler;

    public function __construct(
        EntityManagerInterface $entityManager,
        Manager $timelineManager,
        OptionsFactory $optionsFactory,
        DesktopListMapper $desktopListMapper,
        AccountListManager $accountListManager,
        AwTokenStorageInterface $tokenStorage,
        ProviderHandler $providerHandler
    ) {
        $this->entityManager = $entityManager;
        $this->timelineManager = $timelineManager;
        $this->providerHandler = $providerHandler;
        $this->tokenStorage = $tokenStorage;
        $this->optionsFactory = $optionsFactory;
        $this->desktopListMapper = $desktopListMapper;
        $this->accountListManager = $accountListManager;
    }

    /**
     * @Route("/")
     * @Template("@AwardWalletMain/Manager/CreditCards/suggestionTool.html.twig")
     */
    public function indexAction(UserSpending $userSpending): array
    {
        return [];
    }

    /**
     * @Route("/getUsers", name="aw_manager_cc_suggestion_tool_users")
     */
    public function getUsers(Request $request): JsonResponse
    {
        $q = $request->query->get('term');

        $data = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT
                DISTINCT u.UserID as id, u.Login as text 
            FROM UserCreditCard ucc
            JOIN Usr u ON u.UserID = ucc.UserID
            WHERE
                u.Login LIKE ?
        ',
            [$q . '%'],
            [\PDO::PARAM_STR]
        );

        return new JsonResponse($data);
    }

    /**
     * @Route("/user", name="aw_manager_cc_suggestion_tool_user")
     */
    public function user(Request $request, UserSpending $userSpending): JsonResponse
    {
        $userId = $request->request->getInt('id');

        $jsonData = [
            '%FICO_DATA%' => $this->getFicoAccounts($userId),
            '%USER_CARD_DATA%' => $userSpending->getLastTransactions($userId),
            '%USER_ITINERARIES_DATA%' => $this->getItineraries($userId),
            '%USER_ACCOUNTS_DATA%' => $this->fetchAccounts($userId),
            '%CREDIT_CARDS_DATA%' => $userSpending->getAvailableCars(),
            '%OUTPUT_FORMAT%' => $this->getOutputFormat(),
            '%UNKNOWN_FORMAT%' => $this->getCantRecommended(),
        ];

        $tpl = $this->getPromptTemplate();

        foreach ($jsonData as $key => $value) {
            $tpl = str_replace($key, json_encode($value), $tpl);
        }

        return new JsonResponse([
            'prompt' => $tpl,
            'result' => $userSpending->sendAi($tpl),
        ]);
    }

    private function getFicoAccounts(
        int $userId
    ): array {
        $providersid = array_column(UpdateQsTransactionQmpCommand::PROVIDER_FICO_CODES, 'providerId');
        $codes = array_column(UpdateQsTransactionQmpCommand::PROVIDER_FICO_CODES, 'code');

        $list = $this->entityManager->getConnection()->fetchAllAssociative('
                    SELECT
                            sa.DisplayName, sa.Balance
                    FROM SubAccount sa
                    JOIN Account a ON (a.AccountID = sa.AccountID)
                    WHERE
                            a.ProviderID IN (?)
                        AND a.UserID = ?
                        AND a.UserAgentID IS NULL
                        AND sa.Code IN (?)',
            [$providersid, $userId, $codes],
            [
                \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
                \PDO::PARAM_INT,
                \Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
            ]
        );

        $result = [];

        foreach ($list as $item) {
            $result[$item['DisplayName']] = $item['Balance'];
        }

        return $result;
    }

    private function getCreditCards(int $userId): array
    {
        $result = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT
                    c.CreditCardID, c.CardFullName,
                    ucc.EarliestSeenDate, ucc.LastSeenDate,
                    p.DisplayName
            FROM UserCreditCard ucc
            JOIN CreditCard c ON (c.CreditCardID = ucc.CreditCardID)
            LEFT JOIN Provider p ON (p.ProviderID = c.ProviderID)
            WHERE ucc.UserID = ?
        ',
            [$userId], [\PDO::PARAM_INT]
        );

        return $result;
    }

    private function getItineraries(int $userId): array
    {
        $historyStartDate = new \DateTime('12 months ago');
        $historyEndDate = new \DateTime();

        $user = $this->entityManager->getRepository(Usr::class)->find($userId);
        $queryOptions = (new QueryOptions())
            ->setUser($user)
            ->setWithDetails(true)
            ->setFormat(ItemFormatterInterface::DESKTOP)
            ->setFuture(false)
            ->setMaxSegments(10)
            ->setMaxFutureSegments(100)
            ->setShowDeleted(false)
            ->setStartDate($historyStartDate)
            ->setEndDate($historyEndDate);

        $data = $this->timelineManager->query($queryOptions);
        $result = [];

        foreach ($data as $item) {
            if (in_array($item['type'], ['date'], true)) {
                continue;
            }

            [$type, $itId] = explode('.', $item['id']);

            if (in_array($type, [Reservation::SEGMENT_MAP_END], true)) {
                continue;
            }

            $details = $item['details'] ?? [];

            if (Reservation::SEGMENT_MAP_START === $type) {
                $row = $this->entityManager->getConnection()->fetchAssociative('
                    SELECT
                        r.CreateDate,
                        gt.Address, gt.Lat, gt.Lng, gt.AddressLine, gt.City, gt.State, gt.Country, gt.PostalCode, gt.TimeZoneLocation
                    FROM Reservation r
                    LEFT JOIN GeoTag gt ON (gt.GeoTagID = r.GeoTagID)
                    WHERE
                            ReservationID = ' . ((int) $itId) . ' LIMIT 1 
                ');

                // $checkOut = $this->findPair($item, $data);
                $startDate = new \DateTime('@' . $item['startDate']);
                $endDate = new \DateTime('@' . $item['endDate']);

                [, $hotelName] = explode('@', $item['title']);

                $segment = [
                    'type' => 'hotelReservation',
                    'pricingInfo' => [],
                    'checkInDate' => $startDate->format('c'),
                    'checkOutDate' => $endDate->format('c'),
                    'reservationDate' => (new \DateTime($row['CreateDate']))->format('c'),
                    'hotelName' => $hotelName ?? null,
                    'chainName' => $details['phones']['account']['provider'] ?? $item['origins']['auto'][0]['provider'] ?? null,
                ];

                foreach (
                    [
                        'guestCount' => 'Guests',
                        'kidsCount' => 'Kids',
                        'roomsCount' => 'Room Count',
                    ] as $key => $detailKey
                ) {
                    if ($details[$detailKey] ?? null) {
                        $segment[$key] = $details[$detailKey];
                    }
                }

                if (!empty($row['Address'])) {
                    $segment['address'] = $this->fetchGeoAddress($row);
                }

                $pricing = $item['details']['pricing'] ?? [];

                if (!empty($pricing)) {
                    foreach (
                        [
                            'total' => 'Total Charge',
                            'cost' => 'Cost',
                            'discount' => 'Discount',
                            'spentAwards' => 'Spent Awards',
                            'baseFare' => 'Base Fare',
                            'premiumAddons' => 'Premium add-ons',
                        ] as $key => $detailKey
                    ) {
                        if ($pricing[$detailKey] ?? null) {
                            $segment['pricingInfo'][$key] = $pricing[$detailKey];
                        }
                    }

                    $fees = $this->fetchFees($pricing);

                    if (!empty($fees)) {
                        $segment['pricingInfo']['fees'] = $fees;
                    }
                }

                $result[] = $segment;
            }

            if (Trip::SEGMENT_MAP === $type) {
                $tripSegment = $this->entityManager->getConnection()->fetchAssociative('
                    SELECT
                        s.MarketingAirlineConfirmationNumber, s.OperatingAirlineConfirmationNumber,
                        t.TripID, t.CreateDate,
                        p.ProviderID, p.DisplayName, p.Code
                    FROM TripSegment s
                    JOIN Trip t ON (t.TripID = s.TripID)
                    LEFT JOIN Provider p ON (p.ProviderID = t.ProviderID)
                    WHERE
                            s.TripSegmentID = ' . ((int) $itId) . ' LIMIT 1
                ');
                $trip = $this->entityManager->getConnection()->fetchAssociative('
                    SELECT TripID, EarnedAwards, SpentAwards, Total, Cost, Discount, CurrencyCode, IssuingAirlineConfirmationNumber 
                    FROM Trip t
                    WHERE t.TripID = ' . ((int) $tripSegment['TripID']) . ' LIMIT 1
                ');
                $tripSegments = $this->entityManager->getConnection()->fetchAllAssociative('
                    SELECT
                        *, CabinClass, Duration, TraveledMiles, BookingClass
                    FROM TripSegment s
                    WHERE
                            s.TripID = ' . ((int) $trip['TripID']) . '
                    ORDER BY DepDate ASC 
                ');

                // $flights = $this->findPair($item, $data);

                $segment = [
                    'type' => 'flight',
                    'reservationDate' => (new \DateTime($tripSegment['CreateDate']))->format('c'),
                    'pricingInfo' => [],
                ];

                foreach (
                    [
                        'total' => 'Total',
                        'cost' => 'Cost',
                        'discount' => 'Discount',
                        'spentAwards' => 'SpentAwards',
                        'currencyCode' => 'CurrencyCode',
                    ] as $key => $tripKey
                ) {
                    if (!empty($trip[$tripKey])) {
                        $segment['pricingInfo'][$key] = $trip[$tripKey];
                    }
                }

                if (!empty($segment['pricingInfo']['spentAwards'])) {
                    $segment['pricingInfo']['spentAwards'] .= ' miles';
                }

                $pricing = $item['details']['pricing'] ?? [];

                if (!empty($pricing)) {
                    $fees = $this->fetchFees($pricing);

                    if (!empty($fees)) {
                        $segment['pricingInfo']['fees'] = $fees;
                    }
                }

                if (!empty($tripSegment['ProviderID'])) {
                    $segment['providerInfo'] = [
                        'name' => $tripSegment['DisplayName'],
                        'code' => $tripSegment['Code'],
                    ];

                    if (!empty($trip['EarnedAwards'])) {
                        $segment['providerInfo']['earnedRewards'] = $trip['EarnedAwards'] . ' award miles';
                    }
                }

                $mergeSegments = [];

                foreach ($tripSegments as $tSegment) {
                    $geoTagsId = [];
                    empty($tSegment['DepGeoTagID']) ?: $geoTagsId[] = $tSegment['DepGeoTagID'];
                    empty($tSegment['ArrGeoTagID']) ?: $geoTagsId[] = $tSegment['ArrGeoTagID'];

                    $geoTags = empty($geoTagsId)
                        ? []
                        : $this->entityManager->getConnection()->fetchAllAssociative('
                            SELECT gt.GeoTagID, gt.Address, gt.Lat, gt.Lng, gt.AddressLine, gt.City, gt.State, gt.Country, gt.PostalCode, gt.TimeZoneLocation
                            FROM GeoTag gt
                            WHERE GeoTagID IN (' . implode(',', $geoTagsId) . ')
                        ');
                    $geoTags = array_column($geoTags, null, 'GeoTagID');

                    $mergeSegment = [];

                    foreach (
                        [
                            'traveledMiles' => 'TraveledMiles',
                            'cabin' => 'CabinClass',
                            'duration' => 'Duration',
                            'bookingCode' => 'BookingClass',
                        ] as $key => $skey
                    ) {
                        if (!empty($tSegment[$skey])) {
                            $mergeSegment[$key] = $tSegment[$skey];
                        }
                    }

                    $mergeSegment['departure'] = [
                        'airportCode' => $tSegment['DepCode'],
                        'name' => $tSegment['DepName'],
                        'localDateTime' => (new \DateTime($tSegment['DepDate']))->format('c'),
                        'terminal' => $tSegment['DepartureTerminal'],
                        'address' => $this->fetchGeoAddress($geoTags[$tSegment['DepGeoTagID']] ?? []),
                    ];

                    $mergeSegment['arrival'] = [
                        'airportCode' => $tSegment['ArrCode'],
                        'name' => $tSegment['ArrName'],
                        'localDateTime' => (new \DateTime($tSegment['ArrDate']))->format('c'),
                        'terminal' => $tSegment['ArrivalTerminal'],
                        'address' => $this->fetchGeoAddress($geoTags[$tSegment['ArrGeoTagID']] ?? []),
                    ];

                    $mergeSegments[] = $mergeSegment;
                }

                if (!empty($mergeSegment)) {
                    $segment['segments'] = $mergeSegments;
                }

                $result[] = $segment;
            }
        }

        return ['itineraries' => $result];
    }

    private function findPair($segment, array $data): ?array
    {
        [$type, $id] = explode('.', $segment['id']);

        if (Reservation::SEGMENT_MAP_START === $type) {
            $findId = Reservation::SEGMENT_MAP_END . '.' . $id;
        } elseif (Trip::SEGMENT_MAP === $type) {
        }

        foreach ($data as $item) {
            if (isset($item['id']) && $item['id'] === $findId) {
                return $item;
            }
        }

        return null;
    }

    private function fetchGeoAddress(array $geo): array
    {
        if (empty($geo)) {
            return [];
        }

        $address = [
            'text' => $geo['Address'],
            'addressLine' => $geo['AddressLine'],
            'city' => $geo['City'],
            'stateName' => $geo['State'],
            'countryName' => $geo['Country'],
            'lat' => $geo['Lat'],
            'lng' => $geo['Lng'],
        ];

        if (!empty($geo['PostalCode'])) {
            $address['postalCode'] = $geo['PostalCode'];
        }

        $tz = new \DateTimeZone($geo['TimeZoneLocation']);
        $dateTime = new \DateTime('now', $tz);
        $address['timezone'] = $tz->getOffset($dateTime);

        return $address;
    }

    private function fetchFees($pricing): array
    {
        $fees = [];

        foreach (
            [
                'Tax',
                'Facility Fee',
                'Service fees',
                'CITY TAX',
                'STATE TAX',
                'CONVENTION TAX',
                'OCCUPANCY TAX',
                'U.S. Transportation Tax',
                'U.S. Flight Segment Tax',
                'Passenger Civil Aviation Security Service Fee',
                'U.S. Passenger Facility Charge',
                'United States - September 11th Security Fee(Passenger Civil Aviation Security Service Fee) (AY)',
                'United States - Transportation Tax (US)',
                'United States - Passenger Facility Charge (XF)',
                'United States - Flight Segment Tax (ZP)',
                'Taxes and Fees',
                'Taxes & carrier-imposed fees',
                'Mexico IVA Transportation Tax',
                'U.S. Immigration User Fee',
                'Mexico Immigration Fee DSM',
                'U.S. Customs User Fee',
                'U.S. APHIS User Fee',
                'Mexico Departure Tax',
                'State Tax',
                'Sales Tax',
                'Taxes & carrier-imposed fees',
                // 'Main Cabin Extra (MIA-BOS)',
            ] as $key => $detailKey
        ) {
            if ($pricing[$detailKey] ?? null) {
                $fees[] = [
                    'name' => $detailKey,
                    'charge' => $pricing[$detailKey],
                ];
            }
        }

        return $fees;
    }

    private function fetchAccounts(int $userId): array
    {
        $accounts = $this->getAccounts($userId);

        $list = [];

        foreach ($accounts as $account) {
            if ($account['isCustom']) {
                continue;
            }

            $item = [
                'code' => $account['ProviderCode'],
                'displayName' => $account['DisplayName'],
                'kind' => $this->providerHandler->getLocalizedKind((int) $account['Kind']),
                'balance' => $account['RawBalance'] ?? $account['Balance'],
                'lastDetectedChange' => $account['LastChange'] ?? null,
                'lastRetrieveDate' => $account['SuccessCheckDateYMD'] ?? null,
                'lastChangeDate' => empty($account['LastChangeDateTs'])
                    ? null
                    : (new \DateTime('@' . $account['LastChangeDateTs']))->format('c'),
                'properties' => [],
            ];

            $properties = $account['Properties'] ?? [];
            $setProperties = [
                'NextEliteLevel',
                'AnnualQualifyingSpend',
                'NightsNeededToNextLevel',
                'UntilNextFreeNight',
                'TierMilesToNextTier',
                'TierPoints',
                'MemberSince',
                'LastActivity',
                'QualifyingPoints',
                'StatusQualifyingPoints',
                'Nights',
                'LifetimeStatusCredits',
                'LifetimeMiles',
                'LifetimeMembership',
            ];

            foreach ($setProperties as $property) {
                if (empty($properties[$property])) {
                    continue;
                }

                $item['properties'][] = [
                    'name' => $properties[$property]['Name'],
                    'value' => $properties[$property]['Val'],
                    'kind' => $properties[$property]['Kind'],
                ];
            }

            if (!empty($account['MainProperties']['Status'])) {
                $item['properties'][] = [
                    'name' => $account['MainProperties']['Status']['Caption'],
                    'value' => $account['MainProperties']['Status']['Value'],
                    'rank' => $account['Rank'] ?? 0,
                ];
            }

            if (!empty($account['SubAccountsArray'])) {
                $item['subAccounts'] = [];

                foreach ($account['SubAccountsArray'] as $subAccount) {
                    $subItem = [
                        // 'subAccountId' => $subAccount['SubAccountID'],
                        'displayName' => $subAccount['DisplayName'],
                    ];

                    if (!empty($subAccount['Balance'])) {
                        $subItem['balance'] = $subAccount['Balance'];
                    }

                    if (!empty($subAccount['ExpirationDate'])) {
                        $subItem['expirationDate'] = (new \DateTime($subAccount['ExpirationDate']))->format('c');
                    }

                    $item['subAccounts'][] = $subItem;
                }
            }

            $list[] = $item;
        }

        return ['accounts' => $list];
    }

    private function getAccounts(int $userId): array
    {
        $user = $this->entityManager->getRepository(Usr::class)->find($userId);

        if (null === $user) {
            return [];
        }

        $accounts = $this->accountListManager
            ->getAccountList(
                $this->optionsFactory
                    ->createDefaultOptions()
                    ->set(Options::OPTION_USER, $user)
                    ->set(Options::OPTION_LOAD_SUBACCOUNTS, true)
                    ->set(Options::OPTION_LOAD_MILE_VALUE, true)
                    ->set(Options::OPTION_LOAD_PROPERTIES, true)
            )
            ->getAccounts();

        foreach ($accounts as &$account) {
            $providerId = $account['ProviderID'] ?? 0;

            if (0 === $providerId
                && !empty($account['DisplayName'])
                && AmericanAirlinesAAdvantageDetector::isMatchByName($account['DisplayName'])
            ) {
                $account['ProviderID'] = Provider::AA_ID;
                $account['ProviderCode'] = Provider::AA_CODE;
            }
        }

        return $accounts;
    }

    private function getOutputFormat(): array
    {
        return [
            'NextCardToOpen' => [
                'cardId' => '{integer}',
                'shortReason' => 'Short and catchy phrase describing why this is a good next card for the user',
                'DetailedReason' => 'Detailed explanation of why this is a good next card for the user to open',
                'Pros' => [
                    'Pro 1 for opening this credit card',
                    'Pro 2 for opening this credit card',
                    'Etc.',
                ],
                'Cons' => [
                    'Con 1 for opening this credit card',
                    'Con 2 for opening this credit card',
                    'Etc.',
                ],
            ],
            'NextCardToClose' => [
                'cardId' => '{integer}',
                'shortReason' => 'Short phrase describing why this card is a good candidate for closing',
                'DetailedReason' => 'Detailed explanation of why the user might want to close this card next',
                'Pros' => [
                    'Pro 1 for closing this credit card',
                    'Pro 2 for closing this credit card',
                    'Etc.',
                ],
                'Cons' => [
                    'Con 1 for closing this credit card',
                    'Con 2 for closing this credit card',
                    'Etc.',
                ],
            ],
        ];
    }

    private function getCantRecommended(): array
    {
        return [
            'NextCardToOpen' => 'null',
            'NextCardToClose' => 'null',
        ];
    }

    private function getPromptTemplate(): string
    {
        return trim('
Role
You are a miles and points expert who knows a lot about credit card perks and how to best optimize points usage based on travel goals and on day-to-day credit card spend.

Objective
From the list of available credit cards, select the best card for a user to open, and select one card that this user might consider closing. Give a concise and clear explanation for your choice.

Method
To select which credit card a user should open next consider the following:
1. Credit card sign-up bonus, earning rates, value of the earned currency, flexibility of the earned currency (does it have good transfer partners?), and perks that come with a credit card are all key factors in deciding which credit card a person may want to open next. 
2. People who do not spend a lot on credit cards may have issues with cards that have high spending requirements to receive the sign-up bonus.
3. High yearly fee on the credit card is an issue for users who don’t have much money to spend.
4. The credit score is an important factor; users with a low credit score may not be eligible for some credit cards.
5. 5/24 rule (describe it here)
6. Credit card age is key for the credit score. Never recommend closing the oldest credit card.


Input
The user has the following credit scores:
%FICO_DATA%

The user has the following credit cards at the moment:
%USER_CARD_DATA%

The user has taken the following trips in the past 12 months:
%USER_ITINERARIES_DATA%

The user has the following accounts tracked on AwardWallet:
%USER_ACCOUNTS_DATA%

And these are all the available credit cards that this use can apply for:
%CREDIT_CARDS_DATA%


Output format
Your output should be in the form of JSON with the following structure:
%OUTPUT_FORMAT%

If you can’t recommend any cards to open or to close, please respond with:
%UNKNOWN_FORMAT%


Rules
- Make the explanations easy to read, informal, and to the point.
- For the next card to close, don’t oversell it, this is more of a recommendation. It needs to be soft; it shouldn’t sound like we are pushing the user to close a credit card.
- For the next card to open, make it as appealing to the user as possible. We are in the affiliate referral business; the more convincing you make it sound, the more users we will refer, the more money we will make.

        ');
    }
}

<?php

namespace AwardWallet\MainBundle\Service\StoreLocationFinder;

use AwardWallet\MainBundle\Entity\Repositories\LocationRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\BarcodeCreatorFactory;
use AwardWallet\MainBundle\Globals\Geo;
use AwardWallet\MainBundle\Globals\StringUtils as S;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Tags;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iter\uniformPick;
use function AwardWallet\MainBundle\Globals\Utils\iter\zip;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;
use function iter\product;
use function iter\repeat;

class StoreLocationFinder
{
    private const HUNDRED_METERS_IN_MILES = 0.1 / Geo::KM_IN_MILE;
    private const BARCODE_PROPERTY_ID = 4094;
    private const BARCODE_TYPE_PROPERTY_ID = 4095;
    private const US_COUNTRY_ID = 230;

    private const EXISTING_PLACE_STUB = 'we_have_this_place';
    private const EMPTY_PLACES_RESPONSE_STUB = 'empty_places_response';

    public array $providersWithLocations;

    private PlaceFinder $placeFinder;

    private EntityManagerInterface $entityManager;

    private Connection $connection;

    private LoggerInterface $logger;

    private UsrRepository $usrRepository;

    private LocationRepository $locationRepository;

    private CacheManager $cacheManager;

    private BarcodeCreatorFactory $barcodeCreatorFactory;

    public function __construct(
        PlaceFinder $placeFinder,
        EntityManagerInterface $entityManager,
        Connection $connection,
        LoggerInterface $statLogger,
        UsrRepository $usrRepository,
        LocationRepository $locationRepository,
        CacheManager $cacheManager,
        BarcodeCreatorFactory $barcodeCreatorFactory,
        array $providersWithLocations
    ) {
        $this->placeFinder = $placeFinder;
        $this->entityManager = $entityManager;
        $this->connection = $connection;
        $this->logger = $statLogger;
        $this->usrRepository = $usrRepository;
        $this->locationRepository = $locationRepository;
        $this->cacheManager = $cacheManager;
        $this->barcodeCreatorFactory = $barcodeCreatorFactory;
        $this->providersWithLocations = $providersWithLocations;
    }

    /**
     * @param bool $needToClearEm whether to clear entity manager while iterating over users
     */
    public function findLocationsNearZipArea(StoreFilter $filter, bool $needToClearEm = false): void
    {
        if (null !== ($afterUserId = $filter->getAfterUserId())) {
            $this->logger->info("Starting search from {$afterUserId}", ['_aw_module' => 'store_location_finder']);
        }

        foreach ($this->findLoyaltiesWithBarcodesForUsers($filter) as [$userLoyaltiesCanditatesByName, $userId, $userLat, $userLng]
        ) {
            /** @var Usr $user */
            if (!($user = $this->usrRepository->find($userId))) {
                continue;
            }

            $logContext = ['_aw_module' => 'store_location_finder', 'userid' => $userId];
            $this->logger->info('Processing user ' . $userId . ", filter:" . json_encode($filter), $logContext);

            $trackedCount = 0;
            $storedPointsStmt = $this->locationRepository->getLocationsByUser($user);
            $storedPointsRaw = $storedPointsStmt->fetchAll(\PDO::FETCH_ASSOC);
            $storedPointsStmt->closeCursor();
            $existingPointsByLoyaltyId = [];

            foreach ($storedPointsRaw as $storedPoint) {
                if (1 == $storedPoint['Tracked']) {
                    $trackedCount++;
                }

                $existingPointsByLoyaltyId[
                    strtolower($storedPoint['AccountType'][0]) .
                    "." .
                    ($storedPoint['SubAccountID'] ?? $storedPoint['AccountID'])
                ][] = $storedPoint;
            }

            $this->logger->info('Points: ' . count($storedPointsRaw) . ", tracked: {$trackedCount}", $logContext);

            $userLocationsBudget = $filter->getLocationsLimit() - $trackedCount;

            if ($userLocationsBudget <= 0) {
                $this->logger->info('No slots available, skipping.', $logContext);

                $this->tryClearEm($needToClearEm);

                continue;
            }

            // retrieve EffectiveName's for existing loyalties with points
            $this->enrichLoyaltyData($existingPointsByLoyaltyId);
            $existingloyaltiesWithPointsByEffectiveName = [];

            foreach ($existingPointsByLoyaltyId as $loyaltyId => $loyaltyPoints) {
                foreach ($loyaltyPoints as $loyaltyPoint) {
                    $this->groupByEffectiveName($loyaltyPoint, $existingloyaltiesWithPointsByEffectiveName, true);
                }
            }

            $loyaltiesGroupsByName = [];

            /**
             * $loyaltiesGroupsByName is ordered map, create each group and corresponding map key in specified order:
             *    locations - loyalties with specified "Preferred Store" addresses and barcodes
             *    barcodes  - loylaties with barcodes
             *    existing accounts.
             */

            // add accounts with PreferredStoreAddress-like account properties
            foreach ($userLoyaltiesCanditatesByName['locations'] as $address => $namedLoyaltiesData) {
                // each iteration lazily yields pair of [place, $loyalty] over all possible combinations
                $loyaltiesGroupsByName[$address]['locations'] =
                    product(
                        it($this->placeFinder->getPlacesByAddressIter( // iterator yields places one-by-one for specified search
                            $address,
                            array_merge($logContext, ['loyalties' => array_keys($namedLoyaltiesData)])
                        ))
                            ->orElse([self::EMPTY_PLACES_RESPONSE_STUB])
                            ->take(1) // retrieve only first point for "exact" location
                            ->memoize(), // prevent rewinding

                        $namedLoyaltiesData
                    );
            }

            // add account with barcodes as candidates for insertion
            foreach ($userLoyaltiesCanditatesByName['barcodes'] as $effectiveName => $namedLoyaltiesData) {
                // each iteration lazily yields pair of [place, $loyalty] over all possible combinations
                $loyaltiesGroupsByName[$effectiveName]['barcodes'] =
                    product(
                        it($this->placeFinder->getNearbyPlacesByNameIter( // iterator yields places one-by-one for specified search
                            $userLat,
                            $userLng,
                            $filter->getRadius(),
                            ['keyword' => $effectiveName],
                            array_merge($logContext, ['loyalties' => array_keys($namedLoyaltiesData)])
                        ))
                            ->orElse([self::EMPTY_PLACES_RESPONSE_STUB])
                            ->memoize(),

                        [current($namedLoyaltiesData)] // select first account in group
                    );
            }

            /**
             * Add existing locations with 'we_have_this_place' mark to skip insertion
             * and put them at the beginning of the chain later so loyalties with less
             * points will go first.
             */
            foreach ($existingloyaltiesWithPointsByEffectiveName as $effectiveName => $loyalties) {
                // each tracked point
                $loyaltiesGroupsByName[$effectiveName]['existing'] =
                    zip(
                        repeat(self::EXISTING_PLACE_STUB), // stub place, will be filtered out further

                        it($existingloyaltiesWithPointsByEffectiveName[$effectiveName])
                            ->flatten(1)                   // extract point from each loyalty
                            ->filterByColumn('Tracked', 1) // take only tracked points
                    );
            }

            $loyaltiesGroupsByNameFinal = [];
            // place-loyalty sources generation is over.

            foreach ($loyaltiesGroupsByName as $effectiveName => $loyaltyGroup) {
                // join three subsequences of place-loyalty-like iterators into one in specified order
                $loyaltiesGroupsByNameFinal[$effectiveName] =
                    it($loyaltyGroup['existing'] ?? [])
                    ->chain(
                        $loyaltyGroup['locations'] ?? [],
                        $loyaltyGroup['barcodes'] ?? []
                    )
                    ->onEach(function (array $pair) {
                        [$place, $loyalty] = $pair;

                        if (self::EXISTING_PLACE_STUB !== $place) {
                            $this->updateLastCheckDate($loyalty);
                        }
                    })
                    ->filterPairNotByFirst(self::EMPTY_PLACES_RESPONSE_STUB) // skip empty google result
                    ->take($filter->getLoyaltyLimitPerGroup());
            }

            /**
             * Iterate over all groups of accounts,
             * each iteration uniformly picks one pair [place, loyalty] from each group.
             */
            it(uniformPick(...array_values($loyaltiesGroupsByNameFinal)))
                ->filterPairNotByFirst(self::EXISTING_PLACE_STUB) // skip groups with existing place until groups with less places aligned among all groups
                ->filter(function (array $pair) use (&$existingloyaltiesWithPointsByEffectiveName, $logContext) {
                    [$place, $loyalty] = $pair;

                    // loyalties without groups should
                    if ('none' === $loyalty['EffectiveQuery']) {
                        return true;
                    }

                    // skip existing points in group
                    foreach ($existingloyaltiesWithPointsByEffectiveName[$loyalty['EffectiveQuery']] ?? [] as $storedLoyaltyWithPoints) {
                        foreach ($storedLoyaltyWithPoints as $storedLoyaltyWithPoint) {
                            ['Lat' => $srcLat, 'Lng' => $srcLng] = $storedLoyaltyWithPoint;
                            ['geometry' => ['location' => ['lat' => $dstLat, 'lng' => $dstLng]]] = $place;

                            if (Geo::distance(
                                $srcLat, $srcLng,
                                $dstLat, $dstLng
                            ) < self::HUNDRED_METERS_IN_MILES) {
                                $this->logger->info("skipping existing (geo-filter) loyalty-place \"{$loyalty['EffectiveQuery']}\" at {$srcLat},{$srcLng}", $logContext);

                                return false;
                            }
                        }
                    }

                    return true;
                })
                ->take($userLocationsBudget) // fit into locations budget
                ->forEach(function (array $pair) use (&$existingloyaltiesWithPointsByEffectiveName, $logContext) {
                    [$place, $loyalty] = $pair;
                    $this->saveLocationForLoyalty($place, $loyalty, $logContext);

                    ['geometry' => ['location' => ['lat' => $dstLat, 'lng' => $dstLng]]] = $place;
                    $existingloyaltiesWithPointsByEffectiveName[$loyalty['EffectiveQuery']][$this->getLoyaltyKey($loyalty)][] = array_merge(
                        $loyalty,
                        [
                            'Lat' => $dstLat,
                            'Lng' => $dstLng,
                        ]
                    );
                });

            $this->tryClearEm($needToClearEm);
        }
    }

    protected function tryClearEm(bool $emClear)
    {
        if ($emClear) {
            $this->entityManager->clear();
        }
    }

    protected function enrichLoyaltyData(array &$loyaltiesById)
    {
        $loyaltyIds = [
            'a' => [],
            'c' => [],
        ];

        foreach ($loyaltiesById as $loyaltyPoints) {
            foreach ($loyaltyPoints as $loyaltyPoint) {
                $loyaltyIds[strtolower($loyaltyPoint['AccountType'][0])][] = $loyaltyPoint['SubAccountID'] ?? $loyaltyPoint['AccountID'];
            }
        }

        $stmt = $this->connection->executeQuery(/** @lang MySQL */ "
            (
                select
                    'a' as ContainerType,
                    a.AccountID as ContainerID,
                    coalesce(a.ProgramName, p.ShortName) as LoyaltyName,
                    coalesce(a.LoginURL, p.LoginURL) as LoyaltyURL
                from `Account` a
                left join `Provider` p on 
                    a.ProviderID = p.ProviderID
                where
                    " . ($loyaltyIds['a'] ? 'a.AccountID in (:accountIds)' : '1=0') . "
            )
            union all
            (
                select
                    'c' as ContainerType,
                    pc.ProviderCouponID as ContainerID,
                    pc.ProgramName as LoyaltyName,
                    null as LoyaltyURL
                from `ProviderCoupon` pc
                where
                    " . ($loyaltyIds['c'] ? 'pc.ProviderCouponID in (:couponIds)' : '1=0') . "
            )",
            [
                ':accountIds' => $loyaltyIds['a'],
                ':couponIds' => $loyaltyIds['c'],
            ],
            [
                ':accountIds' => Connection::PARAM_INT_ARRAY,
                ':couponIds' => Connection::PARAM_INT_ARRAY,
            ]
        );

        foreach (stmtAssoc($stmt) as $enrichmentData) {
            $key = $this->getLoyaltyKey($enrichmentData);

            foreach ($loyaltiesById[$key] as $i => $_) {
                $loyaltiesById[$key][$i] = array_merge(
                    $loyaltiesById[$key][$i] ?? [],
                    $enrichmentData
                );
            }
        }
    }

    protected function isProviderBarcodeValid(string $type, string $data): bool
    {
        if ($type === BAR_CODE_QR) {
            $dataLength = mb_strlen($data);

            return $dataLength <= 150 && $dataLength > 0;
        }

        try {
            $barcodeCreator = $this->barcodeCreatorFactory->createBarcodeCreator();
            $barcodeCreator->setFormat($type);
            $barcodeCreator->setNumber($data);
            $barcodeCreator->validate();

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function saveLocationForLoyalty(array $place, array $loyalty, array $logContext = [])
    {
        $insertData = [
            'Name' => isset($place['vicinity']) ? "{$place['name']}, {$place['vicinity']}" : $place['formatted_address'],
            'Lat' => $place['geometry']['location']['lat'],
            'Lng' => $place['geometry']['location']['lng'],
            'Radius' => 100,
            'CreationDate' => (new \DateTime())->format('Y-m-d H:i:s'),
            'IsGenerated' => 1,
        ];

        switch ($loyalty['ContainerType']) {
            case 'AccountWithLocation':
            case 'Account': $insertData['AccountID'] = $loyalty['ContainerID'];

                break;

            case 'Coupon': $insertData['ProviderCouponID'] = $loyalty['ContainerID'];

                break;

            default: throw new \RuntimeException("Unknown container type '{$loyalty['ContainerType']}'");
        }

        $this->connection->beginTransaction();

        try {
            $this->connection->insert('Location', $insertData);
            $this->connection->insert('LocationSetting', [
                'LocationID' => $this->connection->lastInsertId(),
                'Tracked' => 1,
                'UserID' => $loyalty['UserID'],
            ]);
            $this->connection->commit();
            $this->cacheManager->invalidateTags([Tags::getLoyaltyLocationsKey($loyalty['UserID'])]);
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }

        $this->logger->info("saved place '{$place['name']}' (query: '{$loyalty['EffectiveQuery']}') at '" . ($place['vicinity'] ?? $place['formatted_address']) . "' for user: {$loyalty['UserID']}, loyalty: {$loyalty['ContainerType']}.{$loyalty['ContainerID']}, provider: {$loyalty['LoyaltyName']} ({$loyalty['ProviderID']})', link: http://www.google.com/maps/place/" . urlencode("{$place['geometry']['location']['lat']},{$place['geometry']['location']['lng']}"), $logContext);
    }

    protected function findLoyaltiesWithBarcodesForUsers(StoreFilter $filter): \Generator
    {
        if ($this->providersWithLocations) {
            $conds = [];

            foreach ($this->providersWithLocations as $providerCode => $propertyCode) {
                $propertyCode = $this->connection->quote($propertyCode);
                $providerCode = $this->connection->quote($providerCode);

                $conds[] = "(p.Code = {$providerCode} and pp.Code = {$propertyCode})";
            }

            $conds = implode(' or ', $conds);

            $accountsWithLocationsInPropertiesSQL = /** @lang MySQL */ "
                (
                    select
                        distinct ap.AccountID as ContainerID,
                        'AccountWithLocation' as `ContainerType`,
                        p.ShortName as `LoyaltyName`,
                        p.LoginURL as `LoyaltyURL`,
                        p.ProviderID,
                        a.UserID,
                        
                        a.Login,
                        p.BarCode as BarCodeType,
                        
                        pp.Code as LocationPropertyCode,
                        ap.Val as LocationPropertyValue,
                        
                        p.Accounts as Popularity,
                        a.ChangeCount
                    from `Provider` p
                    join `ProviderProperty` pp on
                        p.ProviderID = pp.ProviderID and
                        ({$conds})
                    join `AccountProperty` ap on
                        pp.ProviderPropertyID = ap.ProviderPropertyID and
                        ap.SubAccountID is null
                    join `Account` a on
                        ap.AccountID = a.AccountID
                    where
                        " . ($filter->getAccountIds() ? 'a.AccountID in (:accounts)' : ($filter->getCouponIds() ? '1=0' : '1=1')) . " and
                        " . ($filter->getUserIds() ? 'a.UserID in (:users)' : '1=1') . " and
                        " . (null !== $filter->getAfterUserId() ? 'a.UserID > :afterUserId' : '1=1') . " and
                        p.Code in (:providers) and
                        ap.Val is not null and
                        ap.Val <> '' and
                        a.LastStoreLocationUpdateDate is null
                )
                
                union all ";

            $providersWithLocationsInProperties = array_keys($this->providersWithLocations);
        }

        $stmt = $this->connection->executeQuery(/** @lang MySQL */ "
            select 
                loyalties.*,
                z.Lat,
                z.Lng 
            from (
                (
                    select distinct 
                        accounts.ContainerID,
                        accounts.ContainerType,
                        accounts.LoyaltyName,
                        accounts.LoyaltyURL,
                        accounts.ProviderID,
                        accounts.UserID,
                        
                        apBarCode.Val as ParsedBarCode,
                        apBarCodeType.Val as ParsedBarCodeType,    
                    
                        coalesce(
                            if (apNumber.Val   = '', null, apNumber.Val),
                            if (accounts.Login = '', null, accounts.Login)
                        ) as BarCode,
                        accounts.BarCodeType,
                        
                        clp.Value as ScannedBarCode,
                        
                        accounts.LocationPropertyCode,
                        accounts.LocationPropertyValue,
                        
                        accounts.Popularity,
                        accounts.ChangeCount
                    from (
                        " . ($accountsWithLocationsInPropertiesSQL ?? '') . "
                        (
                            select distinct 
                                a.AccountID as ContainerID,
                                'Account' as `ContainerType`,
                                coalesce(p.ShortName, a.ProgramName) as `LoyaltyName`,
                                coalesce(p.LoginURL, a.LoginURL) as `LoyaltyURL`,
                                p.ProviderID as ProviderID,
                                a.UserID,
                                
                                a.Login,
                                p.BarCode as BarCodeType,
                                
                                null as LocationPropertyCode,
                                null as LocationPropertyValue,
                                
                                p.Accounts as Popularity,
                                a.ChangeCount
                            from `Account` a
                            left join Provider p on a.ProviderID = p.ProviderID
                            left join AccountProperty ap on
                                a.AccountID = ap.AccountID and
                                ap.SubAccountID is null and
                                ap.ProviderPropertyID = " . self::BARCODE_PROPERTY_ID . "
                            where
                                " . ($filter->getAccountIds() ? 'a.AccountID in (:accounts)' : ($filter->getCouponIds() ? '1=0' : '1=1')) . " and
                                " . ($filter->getUserIds() ? 'a.UserID in (:users)' : '1=1') . " and
                                " . (null !== $filter->getAfterUserId() ? 'a.UserID > :afterUserId' : '1=1') . " and
                                (" . (
            $filter->getLoyaltyKinds() ? '
                                        a.Kind in (:loyaltyKinds) or
                                        p.Kind in (:loyaltyKinds)' :
                '1=0'
        ) . " or
                                    p.State = " . PROVIDER_RETAIL . "
                                ) and
                                a.UserAgentID is null and
                                a.LastStoreLocationUpdateDate is null
                        )
                    ) accounts
                    left join `ProviderProperty` ppNumber on
                        accounts.ProviderID = ppNumber.ProviderID and
                        ppNumber.Kind = " . PROPERTY_KIND_NUMBER . "
                        
                    left join `AccountProperty` apNumber on
                        apNumber.AccountID = accounts.ContainerID and
                        apNumber.SubAccountID is null and
                        apNumber.ProviderPropertyID = ppNumber.ProviderPropertyID
                         
                    left join `AccountProperty` apBarCode on
                        apBarCode.AccountID = accounts.ContainerID and
                        apBarCode.SubAccountID is null and
                        apBarCode.ProviderPropertyID = " . self::BARCODE_PROPERTY_ID . "
                        
                    left join `AccountProperty` apBarCodeType on
                        apBarCodeType.AccountID = accounts.ContainerID and
                        apBarCodeType.SubAccountID is null and
                        apBarCodeType.ProviderPropertyID = " . self::BARCODE_TYPE_PROPERTY_ID . "    
                        
                    left join CustomLoyaltyProperty clp on
                        accounts.ContainerID = clp.AccountID and
                        clp.Name = 'BarCodeData'                    
                )
                union all
                (
                    select
                        distinct pc.ProviderCouponID as ContainerID,
                        'Coupon' as `ContainerType`,
                        pc.ProgramName as `LoyaltyName`,
                        null as `LoyaltyURL`,
                        null as ProviderID,
                        pc.UserID,
                        
                        null as ParsedBarCodeType,
                        null as ParsedBarCode,
                        
                        null as BarCode,
                        null as BarCodeType,
                        
                        clp.Value as ScannedBarCode,
                        
                        null as LocationPropertyCode,
                        null as LocationPropertyValue,
                        
                        0 as Popularity,
                        0 as ChangeCount
                    from ProviderCoupon pc
                    join CustomLoyaltyProperty clp on
                        pc.ProviderCouponID = clp.ProviderCouponID and
                        clp.Name = 'BarCodeData'
                    where
                        " . ($filter->getCouponIds() ? 'pc.ProviderCouponID in (:coupons)' : ($filter->getAccountIds() ? '1=0' : '1=1')) . " and 
                        " . ($filter->getUserIds() ? 'pc.UserID in (:users)' : '1=1') . " and
                        " . (null !== $filter->getAfterUserId() ? 'pc.UserID > :afterUserId' : '1=1') . " and
                        " . ($filter->getLoyaltyKinds() ? 'pc.Kind in (:loyaltyKinds)' : '1=1') . " and
                        pc.UserAgentID is null and
                        pc.LastStoreLocationUpdateDate is null
                )
            ) loyalties
            join `Usr` u on
                u.UserID = loyalties.UserID
            join ZipCode z on
                z.Zip = substr(trim(u.Zip), 1, 5)
            where
                u.AccountLevel <> " . ACCOUNT_LEVEL_BUSINESS . " and
                (
                    u.Country in ('US', 'United States', 'U.S.A.', 'USA', 'U.S.') or
                    u.CountryID = " . self::US_COUNTRY_ID . " or
                    u.Country is null
                ) and
                (
                    (
                        loyalties.BarCode <> '' and
                        loyalties.BarCode is not null and
                        
                        loyalties.BarCodeType <> '' and
                        loyalties.BarCodeType is not null
                    ) or
                    (
                        loyalties.ParsedBarCode <> '' and
                        loyalties.ParsedBarCode is not null and
                        
                        loyalties.ParsedBarCodeType <> '' and
                        loyalties.ParsedBarCodeType is not null
                    ) or
                    (
                        loyalties.ScannedBarCode <> '' and 
                        loyalties.ScannedBarCode is not null
                    )
                ) and
                u.ZipCodeUpdateDate is not null and
                trim(u.Zip) regexp '^[0-9]{5}([^0-9]*[0-9]{4})?$'
            order by 
                UserID, 
                (
                    case
                        when loyalties.ContainerType = 'AccountWithLocation' then 0
                        when loyalties.ContainerType = 'Account'             then 1
                        when loyalties.ContainerType = 'Coupon'              then 2
                    end
                ),
                coalesce(loyalties.Popularity, 0) desc,
                loyalties.ChangeCount desc
            ",
            array_merge(
                $filter->getUserIds() ? [':users' => $filter->getUserIds()] : [],
                $filter->getAccountIds() ? [':accounts' => $filter->getAccountIds()] : [],
                $filter->getCouponIds() ? [':coupons' => $filter->getCouponIds()] : [],
                isset($providersWithLocationsInProperties) ? [':providers' => $providersWithLocationsInProperties] : [],
                null !== $filter->getAfterUserId() ? [':afterUserId' => $filter->getAfterUserId()] : [],
                $filter->getLoyaltyKinds() ? [':loyaltyKinds' => $filter->getLoyaltyKinds()] : []
            ),
            array_merge(
                $filter->getUserIds() ? [':users' => Connection::PARAM_INT_ARRAY] : [],
                $filter->getAccountIds() ? [':accounts' => Connection::PARAM_INT_ARRAY] : [],
                $filter->getCouponIds() ? [':coupons' => Connection::PARAM_INT_ARRAY] : [],
                isset($providersWithLocationsInProperties) ? [':providers' => Connection::PARAM_STR_ARRAY] : [],
                null !== $filter->getAfterUserId() ? [':afterUserId' => \PDO::PARAM_INT] : [],
                $filter->getLoyaltyKinds() ? [':loyaltyKinds' => Connection::PARAM_INT_ARRAY] : []
            )
        );

        foreach (stmtAssoc($stmt)->groupAdjacentByColumn('UserID') as $buffer) {
            $newBuffer = [
                'locations' => [],
                'barcodes' => [],
            ];

            [
                'UserID' => $userId,
                'Lat' => $userLat,
                'Lng' => $userLng,
            ] = $buffer[0];

            foreach ($buffer as $fetched) {
                if ( // check barcode existence
                    S::isEmpty($fetched['ScannedBarCode'])
                    && !(
                        S::isAllNotEmpty($fetched['ParsedBarCodeType'], $fetched['ParsedBarCode'])
                        && $this->isProviderBarcodeValid($fetched['ParsedBarCodeType'], $fetched['ParsedBarCode'])
                    )
                    && !(
                        S::isAllNotEmpty($fetched['BarCodeType'], $fetched['BarCode'])
                        && $this->isProviderBarcodeValid($fetched['BarCodeType'], $fetched['BarCode'])
                    )
                ) {
                    continue;
                }

                if ('AccountWithLocation' === $fetched['ContainerType']) {
                    $fetched['EffectiveQuery'] = $this->getEffectiveQuery($fetched);

                    $newBuffer['locations'][$fetched['LocationPropertyValue']][$this->getLoyaltyKey($fetched)] = $fetched;
                } else {
                    // group by "{$loyaltyName} {$loyaltyHost}" scheme
                    $this->groupByEffectiveName($fetched, $newBuffer['barcodes']);
                }
            }

            if (
                count($newBuffer['locations']) +
                count($newBuffer['barcodes'])
            ) {
                yield [$newBuffer, $userId, $userLat, $userLng];
            }
        }

        $stmt->closeCursor();
    }

    protected function groupByEffectiveName(array $fetched, array &$storage, bool $groupByLoyalty = false)
    {
        // group by "{$loyaltyName} {$loyaltyHost}" scheme
        $effectiveQuery = $this->getEffectiveQuery($fetched);
        $fetched['EffectiveQuery'] = $effectiveQuery;

        if ($groupByLoyalty) {
            $storage[$effectiveQuery][$this->getLoyaltyKey($fetched)][] = $fetched;
        } else {
            $storage[$effectiveQuery][$this->getLoyaltyKey($fetched)] = $fetched;
        }
    }

    protected function getEffectiveQuery(array $fetched): string
    {
        $name = null;
        $host = null;

        if (
            S::isNotEmpty($fetched['LoyaltyURL'])
            && (false !== ($parsedPart = parse_url($fetched['LoyaltyURL'], PHP_URL_HOST)))
            && S::isNotEmpty($parsedPart)
        ) {
            $host = $parsedPart;
        }

        if (S::isNotEmpty($fetched['LoyaltyName'])) {
            $name = $fetched['LoyaltyName'];
        }

        if ($effectiveQueryParts = array_filter([$name, $host])) {
            return implode(' ', $effectiveQueryParts);
        }

        return 'none';
    }

    protected function getLoyaltyKey(array $loyalty): string
    {
        return strtolower($loyalty['ContainerType'][0]) . '.' . $loyalty['ContainerID'];
    }

    protected function updateLastCheckDate(array $loyalty)
    {
        $this->connection->update(
            $tableName = (strtolower($loyalty['ContainerType'][0]) === 'c') ? 'ProviderCoupon' : 'Account',
            ['LastStoreLocationUpdateDate' => (new \DateTime())->format('Y-m-d H:i:s')],
            ["{$tableName}ID" => $loyalty['ContainerID']]
        );
    }
}

<?php

namespace AwardWallet\MainBundle\Globals\AccountList\Loader;

use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Currency;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\FrameworkExtension\Error\SafeExecutorFactory;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\Account\SubAccountMatcher;
use AwardWallet\MainBundle\Service\AccountHistory\AnalyserContextFactory;
use AwardWallet\MainBundle\Service\AccountHistory\MerchantDeepLoader;
use AwardWallet\MainBundle\Service\AccountHistory\MerchantRecommendationBuilder;
use AwardWallet\MainBundle\Service\Blog\BlogPostInterface;
use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;
use AwardWallet\MainBundle\Service\LoyaltyLocation;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Service\MileValue\MileValueUserInfo;
use AwardWallet\MainBundle\Service\MileValue\ProviderMileValueItem;
use AwardWallet\MainBundle\Service\ProviderPhoneResolver;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\lazy;
use function iter\chain;

class DataLoader
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var LoyaltyLocation
     */
    private $loyaltyLocation;
    /**
     * @var ProviderPhoneResolver
     */
    private $providerPhoneResolver;
    /**
     * @var GeoLocation
     */
    private $geoLocation;
    /**
     * @var Connection
     */
    private $dbConnection;
    private MileValueService $mileValue;
    private MileValueUserInfo $mileValueUserInfo;
    private SafeExecutorFactory $safeExecutorFactory;
    private TranslatorInterface $translator;
    private BlogPostInterface $blogPost;

    private array $currencyList = [];
    private AnalyserContextFactory $spentAnalyserContextFactory;
    private MerchantDeepLoader $merchantDeepLoader;
    private MerchantRecommendationBuilder $merchantRecommendationBuilder;
    private SubAccountMatcher $subAccountMatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoyaltyLocation $loyaltyLocation,
        ProviderPhoneResolver $providerPhoneResolver,
        GeoLocation $geoLocation,
        MileValueService $mileValue,
        MileValueUserInfo $mileValueUserInfo,
        SafeExecutorFactory $safeExecutorFactory,
        TranslatorInterface $translator,
        BlogPostInterface $blogPost,
        MerchantRecommendationBuilder $merchantRecommendationBuilder,
        AnalyserContextFactory $spentAnalyserContextFactory,
        MerchantDeepLoader $merchantDeepLoader,
        SubAccountMatcher $subAccountMatcher
    ) {
        $this->entityManager = $entityManager;
        $this->dbConnection = $entityManager->getConnection();
        $this->loyaltyLocation = $loyaltyLocation;
        $this->providerPhoneResolver = $providerPhoneResolver;
        $this->geoLocation = $geoLocation;
        $this->mileValue = $mileValue;
        $this->mileValueUserInfo = $mileValueUserInfo;
        $this->safeExecutorFactory = $safeExecutorFactory;
        $this->translator = $translator;
        $this->blogPost = $blogPost;
        $this->spentAnalyserContextFactory = $spentAnalyserContextFactory;
        $this->merchantDeepLoader = $merchantDeepLoader;
        $this->merchantRecommendationBuilder = $merchantRecommendationBuilder;
        $this->subAccountMatcher = $subAccountMatcher;
    }

    public function load(Options $options): LoaderContext
    {
        $loadContext = new LoaderContext();
        $this->loadBaseAccounts($options, $loadContext);
        $this->loadCountries($loadContext);
        $this->loadSubaccounts($options, $loadContext);
        $this->loadCardImages($options, $loadContext);
        $this->loadLoyaltyLocations($options, $loadContext);
        $this->loadCustomLoyaltyProperties($options, $loadContext);

        if (!sizeof($loadContext->accountsIds)) {
            return $loadContext;
        }

        $this->loadProperties($options, $loadContext);
        $this->loadPhones($options, $loadContext);
        $this->loadActiveTripsPresence($options, $loadContext);
        $this->loadHistoryPresence($options, $loadContext);
        $this->loadBalanceChangesCount($options, $loadContext);
        $this->loadPendingScanData($options, $loadContext);
        $this->loadMileValue($options, $loadContext);
        $this->loadBlogPosts($options, $loadContext);
        $this->loadMerchantRecommendations($options, $loadContext);
        $this->loadProviderCodes($loadContext);

        return $loadContext;
    }

    private function loadMileValue(Options $options, LoaderContext $context): void
    {
        if (!$options->get(Options::OPTION_LOAD_MILE_VALUE)) {
            return;
        }

        $context->mileValueDataCache = lazy(fn () => $this->safeExecutorFactory
            ->make(fn () => $this->mileValue->getFlatDataListById())
           ->runOrValue([])
        );

        /** @var ProviderMileValueItem[] $mileValueData */
        $mileValueData = $context->mileValueDataCache->getValue();

        foreach ($context->accounts as &$account) {
            $account['MileValue'] = $this->safeExecutorFactory->make(
                fn () => $this->mileValueUserInfo->fetchAccountInfo($account, $mileValueData)
            )->runOrValue([]);

            if (empty($account['MileValue']) && !empty($account['Currency'])) {
                if (empty($this->currencyList)) {
                    $this->currencyList = $this->entityManager->getRepository(Currency::class)
                        ->getAllPluralLocalizedList($this->translator);
                }

                if (array_key_exists($account['Currency'], $this->currencyList)) {
                    $account['ProviderCurrency'] = $this->currencyList[$account['Currency']][1];
                    $account['ProviderCurrencies'] = $this->currencyList[$account['Currency']][2];
                }
            }
        }
    }

    private function loadBaseAccounts(Options $options, LoaderContext $context): void
    {
        $accountRepository = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Account::class);

        if ($options->get(Options::OPTION_DQL, false)) {
            $rows = $accountRepository->getAccountsArrayByUser(
                $options->get(Options::OPTION_USER)->getUserid(),
                $options->get(Options::OPTION_FILTER, ''),
                $options->get(Options::OPTION_COUPON_FILTER, ''),
                '',
                '',
                false,
                false,
                $options->get(Options::OPTION_STATEFILTER, "a.State > 0")
            );
        } else {
            // Get accounts sql
            $limit = '';

            if (($page = $options->get(Options::OPTION_PAGE, false)) && ($perPage = $options->get(Options::OPTION_PER_PAGE, false))) {
                $limit = 'Limit ' . (($page - 1) * $perPage) . ' , ' . $perPage;
            }
            $sql = $accountRepository->getAccountsSQLByUserAgent(
                $options->get(Options::OPTION_USER)->getUserid(),
                $options->get(Options::OPTION_FILTER, ''),
                $options->get(Options::OPTION_COUPON_FILTER, ''),
                $options->get(Options::OPTION_USERAGENT),
                $options->get(Options::OPTION_STATEFILTER, "a.State > 0"),
                !empty($limit),
                implode("\n", $options->get(Options::OPTION_JOINS)),
                $options->get(Options::OPTION_EXTRA_ACCOUNT_FIELDS),
                $options->get(Options::OPTION_EXTRA_COUPON_FIELDS),
                sprintf(
                    '((%s) OR p.State = %d)',
                    $options->get(Options::OPTION_USER)->getProviderFilter(),
                    PROVIDER_RETAIL
                )
            );
            $orderBy = $this->getOrderBySQL($options);
            $stmt = $this->dbConnection->query($sql . " $orderBy $limit");
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if ($limit) {
                $stmt = $this->dbConnection->query('SELECT FOUND_ROWS()');
                $cnt = $stmt->fetch(\PDO::FETCH_NUM);
                $context->accountsCount = $cnt[0];
            } else {
                $context->accountsCount = count($rows);
            }
        }

        // Fetch result
        foreach ($rows as $row) {
            if (!empty($row['LoginURL'])) {
                if (!preg_match('/^https?:/', $row['LoginURL'])) {
                    $row['LoginURL'] = 'http://' . ltrim($row['LoginURL'], '/');
                }
            }
            $id = $row['ID'];
            $hid = (($row['TableName'] == 'Account') ? 'a' : 'c') . $id;
            $context->accounts[$hid] = $row;

            if ($row['TableName'] == 'Account') {
                $context->accountsIds[] = $id;

                if (isset($row['ProviderID'])) {
                    $context->providerToAccountIdsListMap[$row['ProviderID']][] = $id;
                }
            } else {
                $context->couponsIds[] = $id;
            }

            if ($row['TableName'] == 'Account' && $row['SubAccounts'] > 0) {
                $context->accountsIdsWithSubacc[] = $id;
            }

            if ($row['TableName'] == 'Account' && $row['State'] == ACCOUNT_PENDING) {
                $context->accountsIdsPending[] = $id;
            }

            // # fix custom program login
            $context->accounts[$hid]['MainProperties']['Login'] = $row['Login'];

            // Totals
            if (!isset($context->totals[$row['Kind']])) {
                $context->totals[$row['Kind']] = [
                    "Accounts" => 0,
                    "Points" => 0,
                ];
            }
            $context->totals[$row['Kind']]["Accounts"]++;
            $totalBalance = floatval($row['Balance']);

            if (isset($row['TotalBalance'])) {
                if (is_null($row['TotalBalance'])) {
                    $totalBalance = (float) 0;
                } else {
                    $totalBalance = floatval($row['TotalBalance']);
                }
            }
            $context->totals[$row['Kind']]["Points"] += round($totalBalance);

            // Last updated
            if (isset($row['SuccessCheckDate'])) {
                $date = strtotime($row['SuccessCheckDate']);

                if ($date !== false && (!isset($context->lastUpdated) || $date > $context->lastUpdated)) {
                    $context->lastUpdated = $date;
                }
            }

            $context->accounts[$hid]['Border_LM'] = (bool) $row['Border_LM'];
            $context->accounts[$hid]['Border_DM'] = (bool) $row['Border_DM'];
        }
    }

    private function getOrderBySQL(Options $options): string
    {
        $sort = '';

        if ($options->get(Options::OPTION_GROUPBY) !== null) {
            if ($options->get(Options::OPTION_GROUPBY) == Options::VALUE_GROUPBY_USER) {
                $firstSort = " CASE WHEN UserAgentID IS NULL AND UserID = " . $this->dbConnection->quote($options->get(Options::OPTION_USER)->getUserid(), \PDO::PARAM_INT) . " THEN 1 ELSE 2 END, UserName, ";
            } elseif ($options->get(Options::OPTION_GROUPBY) == Options::VALUE_GROUPBY_KIND) {
                $firstSort = "Kind, CASE WHEN UserAgentID IS NULL AND UserID = " . $this->dbConnection->quote($options->get(Options::OPTION_USER)->getUserid(), \PDO::PARAM_INT) . " THEN 1 ELSE 2 END, UserName, ";
            }
        } else {
            $firstSort = "";
        }

        if ($options->get(Options::OPTION_ORDERBY) !== null) {
            switch ($options->get(Options::OPTION_ORDERBY)) {
                case Options::VALUE_ORDERBY_CARRIER:
                    $sort = "ORDER BY {$firstSort}DisplayName, RawBalance DESC";

                    break;

                case Options::VALUE_ORDERBY_CARRIER_DESC:
                    $sort = "ORDER BY {$firstSort}DisplayName DESC, RawBalance DESC";

                    break;

                case Options::VALUE_ORDERBY_PROGRAM:
                    $sort = "ORDER BY {$firstSort}DisplayName, RawBalance DESC";

                    break;

                case Options::VALUE_ORDERBY_PROGRAM_DESC:
                    $sort = "ORDER BY {$firstSort}DisplayName DESC, RawBalance DESC";

                    break;

                case Options::VALUE_ORDERBY_BALANCE:
                    $sort = "ORDER BY {$firstSort}RawBalance DESC";

                    break;

                case Options::VALUE_ORDERBY_BALANCE_DESC:
                    $sort = "ORDER BY {$firstSort}RawBalance ASC";

                    break;

                case Options::VALUE_ORDERBY_EXPIRATION:
                    $sort = "ORDER BY {$firstSort}IsNull(ExpirationDate), ExpirationDate ASC, CanCheckExpiration DESC";

                    break;

                case Options::VALUE_ORDERBY_EXPIRATION_DESC:
                    $sort = "ORDER BY {$firstSort}IsNull(ExpirationDate) DESC, ExpirationDate DESC, CanCheckExpiration DESC";

                    break;

                case Options::VALUE_ORDERBY_LASTUPDATE:
                    $sort = "
                        ORDER BY {$firstSort}
                            CASE WHEN TableName = 'Coupon' OR ProviderID IS NULL THEN 2 ELSE 1 END,
                            CASE
                                WHEN SuccessCheckDate IS NULL AND LastChangeDate IS NULL THEN 1 ELSE 0
                            END DESC,
                            CASE
                                WHEN SuccessCheckDate IS NULL THEN LastChangeDate
                                WHEN LastChangeDate IS NULL THEN SuccessCheckDate
                                ELSE GREATEST(SuccessCheckDate, LastChangeDate)
                            END ASC
                    ";

                    break;

                case Options::VALUE_ORDERBY_LASTUPDATE_DESC:
                    $sort = "
                        ORDER BY {$firstSort}
                            CASE WHEN TableName = 'Coupon' OR ProviderID IS NULL THEN 2 ELSE 1 END,
                            CASE
                                WHEN SuccessCheckDate IS NULL AND LastChangeDate IS NULL THEN 1 ELSE 0
                            END ASC,
                            CASE
                                WHEN SuccessCheckDate IS NULL THEN LastChangeDate
                                WHEN LastChangeDate IS NULL THEN SuccessCheckDate
                                ELSE GREATEST(SuccessCheckDate, LastChangeDate)
                            END DESC
                    ";

                    break;

                case Options::VALUE_ORDERBY_LASTCHANGEDATE_DESC:
                    $sort = "ORDER BY {$firstSort}CASE WHEN TableName = 'Coupon' OR ProviderID IS NULL THEN 2 ELSE 1 END, IsNull(LastChangeDate), LastChangeDate DESC";

                    break;

                default:
                    $sort = "ORDER BY {$firstSort}DisplayName, RawBalance DESC";
            }
        } else {
            $sort = "ORDER BY {$firstSort}DisplayName, Balance DESC";
        }

        return $sort;
    }

    private function loadCountries(LoaderContext $context): void
    {
        if ($context->accountsIds) {
            // Load countries
            $countriesStmt = $this->dbConnection->executeQuery("
                select
                    a.AccountID, 
                    pc.*
                from `Account` a
                join `Provider` p on a.ProviderID = p.ProviderID
                join `ProviderCountry` pc on p.ProviderID = pc.ProviderID
                where
                    a.AccountID in (?) and
                    pc.LoginURL <> ''",
                [$context->accountsIds],
                [Connection::PARAM_INT_ARRAY]
            );

            while ($country = $countriesStmt->fetch(\PDO::FETCH_ASSOC)) {
                $context->accounts['a' . $country['AccountID']]['Countries'][$country['CountryID']] = $country;
            }

            // prefetch accounts, providers to prevent one-by-one fetching later
            $q = $this->entityManager->createQuery("select a, p 
            from AwardWallet\MainBundle\Entity\Account a
            join a.providerid p
            where a.accountid in (:accountIds)");
            $q->execute(['accountIds' => $context->accountsIds]);
        }
    }

    private function loadSubaccounts(Options $options, LoaderContext $context): void
    {
        // Get subaccounts
        if ($options->get(Options::OPTION_LOAD_SUBACCOUNTS) && sizeof($context->accountsIdsWithSubacc)) {
            $sql = "
				SELECT   s.*, c.DisplayNameFormat, c.IsCashBackOnly, c.CashBackType, trim(trailing '.' FROM trim(trailing '0' FROM ROUND(Balance, 10))) as Balance
				FROM     SubAccount s
				  LEFT JOIN CreditCard c ON s.CreditCardID = c.CreditCardID
				WHERE    AccountID IN (?)
				ORDER BY DisplayName
			";
            $stmt = $this->dbConnection->executeQuery($sql,
                [$context->accountsIdsWithSubacc],
                [Connection::PARAM_INT_ARRAY]
            );
            $accountLastChangeReSet = [];

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $id = $row['SubAccountID'];
                $aid = 'a' . $row['AccountID'];

                if (!isset($context->accounts[$aid]['SubAccountsArray'])) {
                    $context->accounts[$aid]['SubAccountsArray'] = [];
                }

                // Disable ExpirationDate if balance eq 0, see http://redmine.awardwallet.com/issues/13534#note-2
                if ($row['Balance'] == "0") {
                    $row['ExpirationDate'] = null;
                }

                if ((int) $row['CreditCardID'] > 0 && !empty($row['DisplayNameFormat'])) {
                    $row['DisplayName'] = CreditCard::formatCreditCardName(
                        $row['DisplayName'], $row['DisplayNameFormat'], (int) $context->accounts[$aid]['ProviderID']
                    );
                    $row['IsCashBackOnly'] = (bool) $row['IsCashBackOnly'];
                }

                $context->accounts[$aid]['SubAccountsArray'][$id] = $row;
                $context->accounts[$aid]['SubAccountsArray'][$id]['SubAccountCode'] = $row['Code'];
                $context->accounts[$aid]['SubAccountsArray'][$id]['isCoupon'] = (isset($row['Kind']) && $row['Kind'] == 'C');

                if ($row['LastChangeDate'] === $context->accounts[$aid]['LastChangeDate']) {
                    $accountLastChangeReSet[] = $context->accounts[$aid]['ID'];
                }
            }

            // https://redmine.awardwallet.com/issues/16415#note-9
            if (!empty($accountLastChangeReSet)) {
                $balanceUpdateDate = $this->dbConnection->fetchAll(
                    'SELECT AccountID, MAX(UpdateDate) as LastUpdateDate FROM AccountBalance WHERE AccountID IN (?) AND SubAccountID IS NULL GROUP BY AccountID',
                    [array_unique($accountLastChangeReSet)], [Connection::PARAM_INT_ARRAY]
                );

                foreach ($balanceUpdateDate as $accBalance) {
                    $context->accounts['a' . $accBalance['AccountID']]['LastChangeDate'] = $accBalance['LastUpdateDate'];
                }
            }
        }
    }

    private function loadCardImages(Options $options, LoaderContext $context): void
    {
        if (
            $options->get(Options::OPTION_LOAD_CARD_IMAGES)
            && ($context->accountsIdsWithSubacc || $context->accountsIds || $context->couponsIds)
        ) {
            $cardImageFields = [
                'ci.CardImageID',
                'ci.UserID',
                'ci.AccountID',
                'ci.SubAccountID',
                'ci.ProviderCouponID',
                'ci.Kind',
                'ci.Width',
                'ci.Height',
                'ci.FileName',
                'ci.FileSize',
                'ci.Format',
                'ci.StorageKey',
                'ci.UploadDate',
                'ci.ClientUUID',
                'ci.DetectedProviderID',
                'ci.ProviderID',
                'ci.UUID',
                'ci.CCDetected',
                'ci.CCDetectorVersion',
            ];
            $cardImageFields = implode(',', $cardImageFields);
            $cardImagesStmt = $this->dbConnection->executeQuery(/** @lang MySQL */ "
                    (
                        select
                            {$cardImageFields},
                            null as ParentAccountID
                        from Account a
                        join CardImage ci on 
                            ci.AccountID = a.AccountID 
                        where a.AccountID in (?)
                    )
                    union all
                    (
                        select
                            {$cardImageFields},
                            sa.AccountID as ParentAccountID
                        from SubAccount sa
                        join CardImage ci on
                            ci.SubAccountID = sa.SubAccountID
                        where sa.AccountID in (?)       
                    )
                    union all
                    (
                        select
                             {$cardImageFields},
                             null as ParentAccountID
                        from ProviderCoupon pc
                        join CardImage ci on
                            pc.ProviderCouponID = ci.ProviderCouponID
                        where pc.ProviderCouponID in (?)
                    )
                ",
                [
                    $context->accountsIds ?: [-1],
                    $context->accountsIdsWithSubacc ?: [-1],
                    $context->couponsIds ?: [-1],
                ],
                [
                    Connection::PARAM_INT_ARRAY,
                    Connection::PARAM_INT_ARRAY,
                    Connection::PARAM_INT_ARRAY,
                ]
            );

            while ($cardImage = $cardImagesStmt->fetch(\PDO::FETCH_ASSOC)) {
                $cardImage['AccountID'] = $cardImage['ParentAccountID'] ?? $cardImage['AccountID'];
                $kind = (int) $cardImage['Kind'];

                if (isset(
                    $cardImage['SubAccountID'],
                    $cardImage['AccountID']
                )) {
                    if (isset(
                        $context->accounts['a' . $cardImage['AccountID']]['SubAccountsArray'][$cardImage['SubAccountID']]
                    )) {
                        $context->accounts['a' . $cardImage['AccountID']]['SubAccountsArray'][$cardImage['SubAccountID']]['CardImages'][$kind] = $cardImage;
                    }
                } elseif (isset($cardImage['AccountID'])) {
                    $context->accounts['a' . $cardImage['AccountID']]['CardImages'][$kind] = $cardImage;
                } elseif (isset($cardImage['ProviderCouponID'])) {
                    $context->accounts['c' . $cardImage['ProviderCouponID']]['CardImages'][$kind] = $cardImage;
                }
            }

            $docImagesStmt = $this->dbConnection->executeQuery('
                select di.*
                from DocumentImage di
                where di.ProviderCouponID in (?)
                order by di.DocumentImageID, di.ProviderCouponID, di.UploadDate',
                [$context->couponsIds ?: [-1]],
                [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
            );

            while ($documentImage = $docImagesStmt->fetch(\PDO::FETCH_ASSOC)) {
                $context->accounts['c' . $documentImage['ProviderCouponID']]['DocumentImages'][] = $documentImage;
            }
        }
    }

    private function loadLoyaltyLocations(Options $options, LoaderContext $context): void
    {
        if ($options->get(Options::OPTION_LOAD_LOYALTY_LOCATIONS)) {
            $locations = $this->loyaltyLocation->getLocations($options->get(Options::OPTION_USER));

            foreach ($locations as $locationRow) {
                if (in_array($locationRow['AccountType'], ["Account", "AccountShare"])) {
                    if (isset($context->accounts['a' . $locationRow['AccountID']])) {
                        $context->accounts['a' . $locationRow['AccountID']]['StoreLocations'][] = $locationRow;
                    }
                } elseif (in_array($locationRow['AccountType'], ["SubAccount", "SubAccountShare"])) {
                    if (isset($context->accounts['a' . $locationRow['AccountID']]['SubAccountsArray'][$locationRow['SubAccountID']])) {
                        $context->accounts['a' . $locationRow['AccountID']]['SubAccountsArray'][$locationRow['SubAccountID']]['StoreLocations'][] = $locationRow;
                    }
                } elseif (in_array($locationRow['AccountType'], ["Coupon", "CouponShare"])) {
                    if (isset($context->accounts['c' . $locationRow['AccountID']])) {
                        $context->accounts['c' . $locationRow['AccountID']]['StoreLocations'][] = $locationRow;
                    }
                }
            }
        }
    }

    private function loadCustomLoyaltyProperties(Options $options, LoaderContext $context): void
    {
        if (
            $options->get(Options::OPTION_LOAD_PROPERTIES)
            && ($context->accountsIdsWithSubacc || $context->accountsIds || $context->couponsIds)
        ) {
            $customLoyaltyPropertiesStmt = $this->dbConnection->executeQuery(/** @lang MySQL */ "
                    (
                        select
                            clp.*,
                            null as ParentAccountID
                        from Account a
                        join CustomLoyaltyProperty clp on 
                            clp.AccountID = a.AccountID 
                        where a.AccountID in (?)
                    )
                    union all
                    (
                        select
                            clp.*,
                            sa.AccountID as ParentAccountID
                        from SubAccount sa
                        join CustomLoyaltyProperty clp on
                            clp.SubAccountID = sa.SubAccountID
                        where sa.AccountID in (?)       
                    )
                    union all
                    (
                        select
                             clp.*,
                             null as ParentAccountID
                        from ProviderCoupon pc
                        join CustomLoyaltyProperty clp on
                            pc.ProviderCouponID = clp.ProviderCouponID
                        where pc.ProviderCouponID in (?)
                    )
                ",
                [
                    $context->accountsIds ?: [-1],
                    $context->accountsIdsWithSubacc ?: [-1],
                    $context->couponsIds ?: [-1],
                ],
                [
                    Connection::PARAM_INT_ARRAY,
                    Connection::PARAM_INT_ARRAY,
                    Connection::PARAM_INT_ARRAY,
                ]
            );

            while ($customLoyaltyProperty = $customLoyaltyPropertiesStmt->fetch(\PDO::FETCH_ASSOC)) {
                $customLoyaltyProperty['AccountID'] = $customLoyaltyProperty['ParentAccountID'] ?? $customLoyaltyProperty['AccountID'];
                $name = $customLoyaltyProperty['Name'];

                if (isset(
                    $customLoyaltyProperty['SubAccountID'],
                    $customLoyaltyProperty['AccountID']
                )) {
                    if (isset(
                        $context->accounts['a' . $customLoyaltyProperty['AccountID']]['SubAccountsArray'][$customLoyaltyProperty['SubAccountID']]
                    )) {
                        $context->accounts['a' . $customLoyaltyProperty['AccountID']]['SubAccountsArray'][$customLoyaltyProperty['SubAccountID']]['CustomLoyaltyProperties'][$name] = $customLoyaltyProperty['Value'];
                    }
                } elseif (isset($customLoyaltyProperty['AccountID'])) {
                    $context->accounts['a' . $customLoyaltyProperty['AccountID']]['CustomLoyaltyProperties'][$name] = $customLoyaltyProperty['Value'];
                } elseif (isset($customLoyaltyProperty['ProviderCouponID'])) {
                    $context->accounts['c' . $customLoyaltyProperty['ProviderCouponID']]['CustomLoyaltyProperties'][$name] = $customLoyaltyProperty['Value'];
                }
            }
        }
    }

    private function loadProperties(Options $options, LoaderContext $context): void
    {
        if ($options->get(Options::OPTION_LOAD_PROPERTIES)) {
            $sql = "
				SELECT ap.AccountID   ,
						 ap.SubAccountID,
						 pp.Name        ,
						 pp.Code        ,
						 ap.Val         ,
						 pp.ProviderPropertyID,
						 pp.Kind        ,
						 pp.Type        ,
						 pp.Visible     ,
						 pp.ProviderID  ,
						 pp.SortIndex
				FROM     AccountProperty ap force index (AccountID)
						 JOIN ProviderProperty pp force index (`PRIMARY`)
						 ON       ap.ProviderPropertyID = pp.ProviderPropertyID
				WHERE    ap.AccountID IN (?)
			";
            $data = $this->dbConnection->executeQuery($sql,
                [$context->accountsIds],
                [Connection::PARAM_INT_ARRAY]
            )->fetchAll(\PDO::FETCH_ASSOC);
            usort($data, function ($a, $b) {
                return $a['SortIndex'] - $b['SortIndex'];
            });

            foreach ($data as $row) {
                $add = true;
                $hid = 'a' . $row['AccountID']; // keys like "a13289782" for accounts
                $isSubAcc = (isset($row['SubAccountID']) && $row['SubAccountID'] != '');

                if ($isSubAcc && isset($context->accounts[$hid]['SubAccountsArray'][$row['SubAccountID']])) {
                    $propField = &$context->accounts[$hid]['SubAccountsArray'][$row['SubAccountID']]['Properties'];
                } elseif ($isSubAcc && !empty($row['SubAccountID'])) {
                    if ('BalanceInTotalSum' == $row['Code']) {
                        $context->accounts[$hid]['Properties']['BalanceInTotalSum'] = $row;
                    }

                    continue;
                } elseif (empty($row['SubAccountID'])) {
                    $propField = &$context->accounts[$hid]['Properties'];
                }

                if (!isset($propField) || !is_array($propField)) {
                    $propField = [];
                }

                if ($isSubAcc && isset($context->accounts[$hid]['SubAccountsArray'][$row['SubAccountID']])) {
                    if (!isset($context->accounts[$hid]['SubAccountsArray'][$row['SubAccountID']]['MainProperties'])) {
                        $context->accounts[$hid]['SubAccountsArray'][$row['SubAccountID']]['MainProperties'] = [];
                    }
                    $mainPropField = &$context->accounts[$hid]['SubAccountsArray'][$row['SubAccountID']]['MainProperties'];
                } else {
                    if (!isset($context->accounts[$hid]['MainProperties'])) {
                        $context->accounts[$hid]['MainProperties'] = [];
                    }
                    $mainPropField = &$context->accounts[$hid]['MainProperties'];
                }

                if ($row['Kind'] == PROPERTY_KIND_NUMBER) {
                    $mainPropField['Number'] = [
                        'Field' => $row['Code'],
                        'Caption' => $row['Name'],
                        'Number' => $row['Val'],
                    ];
                    $mainPropField['Login'] = $row['Val'];
                    $add = false;
                } elseif (!isset($mainPropField['Login'])) {
                    $mainPropField['Login'] = $context->accounts[$hid]['Login'];
                }

                // detected Cards
                if ($row['Code'] == "DetectedCards" && $context->accounts[$hid]['Kind'] == PROVIDER_KIND_CREDITCARD) {
                    $add = false;
                    $allDetectedCards = @unserialize($row['Val']);
                    $countDetectedCards = count($allDetectedCards);

                    if (is_array($allDetectedCards) && $countDetectedCards > 0) {
                        // showing Detected Cards
                        if (
                            //                            isset($accounts[$hid]['SubAccountsArray'])
                            $allDetectedCards > 0
                            && in_array((int) $context->accounts[$hid]['ProviderID'], Provider::EARNING_POTENTIAL_LIST)
                        ) {
                            foreach ($allDetectedCards as &$detectedCardItem) {
                                $allSubAccounts = $context->accounts[$hid]['SubAccountsArray'] ?? [];
                                $detectedCardItem['ParsedDisplayName'] = $detectedCardItem['DisplayName'];

                                foreach ($allSubAccounts as $subAccItem) {
                                    if ($detectedCardItem['Code'] !== $subAccItem['Code']) {
                                        continue;
                                    }
                                    $detectedCardItem['DisplayName'] = $subAccItem['DisplayName'];
                                    $detectedCardItem['IsCashBackOnly'] = (bool) $subAccItem['IsCashBackOnly'];
                                    $detectedCardItem['CashBackType'] = $subAccItem['CashBackType'];
                                    $detectedCardItem['CreditCardID'] = $subAccItem['CreditCardID'] ?? '';
                                }
                            }

                            unset($detectedCardItem);
                        }

                        $mainPropField['DetectedCards'] = [
                            'Field' => $row['Code'],
                            'Caption' => $row['Name'],
                            'DetectedCards' => $allDetectedCards,
                        ];
                    }
                }

                // lookup support phone
                if (!isset($context->accountsIdsSupportPhone[$row['AccountID']])) {
                    $context->accountsIdsSupportPhone[$row['AccountID']] = [
                        'status' => null,
                        'provider' => $context->accounts[$hid]['ProviderID'],
                    ];
                }

                if ($row['Kind'] == PROPERTY_KIND_STATUS) {
                    $eliteLevelFields = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Elitelevel::class)->getEliteLevelFields($row['ProviderID'], $row['Val']);
                    $mainPropField['Status'] = [
                        'Field' => $row['Code'],
                        'Caption' => $row['Name'],
                        'Status' => $eliteLevelFields['Name'] ?? $row['Val'],
                        'Value' => $row['Val'],
                    ];
                    $context->accountsIdsSupportPhone[$row['AccountID']]['status'] = array_merge(
                        [$row['Val']],
                        isset($eliteLevelFields['Name']) ? [$eliteLevelFields['Name']] : []
                    );
                }

                $row['Name'] = htmlspecialchars_decode($row['Name']);

                if ($add) {
                    $propField[$row['Code']] = $row;
                }
            }

            foreach (array_unique(array_column($data, 'AccountID')) as $accountId) {
                $hid = 'a' . $accountId;

                if (isset($context->accounts[$hid]['SubAccountsArray']) && is_array($context->accounts[$hid]['SubAccountsArray'])) {
                    $context->accounts[$hid]['fullSubAccountsArray'] = $context->accounts[$hid]['SubAccountsArray'];

                    $context->accounts[$hid]['SubAccountsArray'] = array_filter(
                        $context->accounts[$hid]['SubAccountsArray'],
                        static function ($subAccount) {
                            return (int) $subAccount['IsHidden'] === 0;
                        }
                    );
                }
            }
        }
    }

    private function loadPhones(Options $options, LoaderContext $context): void
    {
        if ($phonesOption = $options->get(Options::OPTION_LOAD_PHONES)) {
            // Support Phones
            if (sizeof($context->accountsIdsSupportPhone)) {
                $accountsForPhone = [];
                $country = null;
                $user = $options->get(Options::OPTION_USER);
                $userCountryId = $user->getCountryid();

                if ($userCountryId) {
                    $c = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Country::class)->find($userCountryId);

                    if ($c) {
                        $country = $c->getName();
                    }
                }

                if (
                    !isset($country)
                    && $options->has(Options::OPTION_CLIENTIP)
                    && StringUtils::isNotEmpty($clientIp = $options->get(Options::OPTION_CLIENTIP))
                ) {
                    $countryEntity = $this->geoLocation->getCountryByIp($clientIp);

                    if ($countryEntity instanceof Country) {
                        $country = $countryEntity->getName();
                    }
                }

                foreach ($context->accountsIdsSupportPhone as $accId => $fields) {
                    $accountsForPhone[] = [
                        'account' => $accId,
                        'provider' => $fields['provider'],
                        'status' => $fields['status'],
                        'country' => $country,
                    ];
                }

                $phones = $this->providerPhoneResolver->getUsefulPhones($accountsForPhone);
                $currentAccount = 0;

                foreach ($phones as $phone) {
                    if (Options::VALUE_PHONES_FULL === $phonesOption) {
                        $context->accounts['a' . $phone['AccountID']]['Phones'][] = $phone;
                    }

                    if ($phone['AccountID'] != $currentAccount) {
                        $currentAccount = $phone['AccountID'];
                        $hid = 'a' . $phone['AccountID'];

                        if (!isset($context->accounts[$hid]['Properties'])) {
                            $context->accounts[$hid]['Properties'] = [];
                        }

                        if (isset($context->accounts[$hid]['Properties']['CustomerSupportPhone'])) {
                            continue;
                        }
                        $context->accounts[$hid]['Properties']['CustomerSupportPhone'] = [
                            'Name' => 'Customer Support Phone',
                            'Code' => 'CustomerSupportPhone',
                            'Val' => $phone['Phone'],
                            'Kind' => null,
                            'Visible' => 1,
                        ];
                    }
                }
            }
        }
    }

    private function loadHistoryPresence(Options $options, LoaderContext $context): void
    {
        if ($options->get(Options::OPTION_LOAD_HISTORY_PRESENCE)) {
            $presenceSources = [];

            if ($context->accountsIds) {
                $presenceSources[] = (function () use ($context) {
                    yield from $this->dbConnection->executeQuery("
                        select 
                           Account.AccountID,
                           null as SubAccountID
                        from Account
                        where Account.AccountID in (:accountIds) and
                        exists (
                            select * from AccountHistory
                            where 
                                  AccountHistory.AccountID = Account.AccountID and
                                  AccountHistory.SubAccountID is null
                        )",
                        [':accountIds' => $context->accountsIds],
                        [':accountIds' => Connection::PARAM_INT_ARRAY]
                    );
                })();
            }

            if ($context->accountsIdsWithSubacc) {
                $presenceSources[] = (function () use ($context) {
                    yield from $this->dbConnection->executeQuery("
                        select 
                           SubAccount.AccountID, 
                           SubAccount.SubAccountID
                        from SubAccount
                        where SubAccount.AccountID in (:accountIds) and
                        exists (
                            select * from AccountHistory
                            where 
                                  AccountHistory.SubAccountID = SubAccount.SubAccountID
                        )",
                        [':accountIds' => $context->accountsIdsWithSubacc],
                        [':accountIds' => Connection::PARAM_INT_ARRAY]
                    );
                })();
            }

            foreach (chain(...$presenceSources) as ['AccountID' => $accountId,
                'SubAccountID' => $subAccountId, ]) {
                $hid = 'a' . $accountId;

                if (isset($subAccountId, $accountId)) {
                    if (isset($context->accounts[$hid]['SubAccountsArray'][$subAccountId])) {
                        $context->accounts[$hid]['SubAccountsArray'][$subAccountId]['HasHistory'] = true;
                    }
                } else {
                    $context->accounts[$hid]['HasHistory'] = true;
                }
            }
        }
    }

    private function loadBalanceChangesCount(Options $options, LoaderContext $context): void
    {
        if (!$options->get(Options::OPTION_LOAD_BALANCE_CHANGES_COUNT)) {
            return;
        }

        $changesCountSources = [];

        if ($context->accountsIds) {
            $changesCountSources[] = (function () use ($context) {
                yield from $this->dbConnection->executeQuery("
                    select 
                       Account.AccountID,
                       null as SubAccountID,
                       count(*) as Count
                    from Account
                    join AccountBalance on
                        AccountBalance.AccountID = Account.AccountID and
                        AccountBalance.SubAccountID is null
                    where 
                        Account.AccountID in (:accountIds)
                    group by Account.AccountID
                    ",
                    [':accountIds' => $context->accountsIds],
                    [':accountIds' => Connection::PARAM_INT_ARRAY]
                );
            })();
        }

        if ($context->accountsIdsWithSubacc) {
            $changesCountSources[] = (function () use ($context) {
                yield from $this->dbConnection->executeQuery("
                    select 
                       Account.AccountID,
                       AccountBalance.SubAccountID,
                       count(*) as Count
                    from Account
                    join AccountBalance on
                        AccountBalance.AccountID = Account.AccountID and
                        AccountBalance.SubAccountID is not null
                    where 
                        Account.AccountID in (:accountIds)
                    group by Account.AccountID, AccountBalance.SubAccountID
                    ",
                    [':accountIds' => $context->accountsIdsWithSubacc],
                    [':accountIds' => Connection::PARAM_INT_ARRAY]
                );
            })();
        }

        foreach (chain(...$changesCountSources) as [
            'AccountID' => $accountId,
            'SubAccountID' => $subAccountId,
            'Count' => $count,
        ]
        ) {
            $hid = 'a' . $accountId;

            if (isset($subAccountId, $accountId)) {
                if (isset($context->accounts[$hid]['SubAccountsArray'][$subAccountId])) {
                    $context->accounts[$hid]['SubAccountsArray'][$subAccountId]['BalanceChangesCount'] = $count;
                }
            } else {
                $context->accounts[$hid]['BalanceChangesCount'] = $count;
            }
        }
    }

    private function loadActiveTripsPresence(Options $options, LoaderContext $context): void
    {
        if ($options->get(Options::OPTION_LOAD_HAS_ACTIVE_TRIPS)) {
            $ids = array_flip($context->accountsIds);

            if (count($ids) > 0) {
                $date = date('Y-m-d H:i:s', time() - TRIPS_PAST_DAYS * SECONDS_PER_DAY);
                $sql =
                    "SELECT
				    t.AccountID, max(ts.TripSegmentID) AS ID
				FROM
				    Trip t
                    JOIN TripSegment ts
                    ON t.TripID = ts.TripID
				WHERE
				    t.AccountID IN (:ids)
				    AND t.Hidden = 0
				    AND ts.ArrDate > :date
				GROUP BY t.AccountID
                UNION 
                SELECT
                    AccountID, max(RentalID) AS ID
                FROM
                    Rental
                WHERE
                    AccountID IN (:ids)
                    AND Hidden = 0
                    AND DropoffDatetime > :date
				GROUP BY AccountID
				UNION
                SELECT
                    AccountID, max(ReservationID) AS ID
                FROM
                    Reservation
                WHERE
                    AccountID IN (:ids)
                    AND Hidden = 0
                    AND CheckOutDate > :date
				GROUP BY AccountID
                UNION 
                SELECT
                    AccountID, max(RestaurantID) AS ID
                FROM
                    Restaurant
                WHERE
                    AccountID IN (:ids)
                    AND Hidden = 0
                    AND COALESCE(EndDate, StartDate) > :date
				GROUP BY AccountID
                UNION 
                SELECT
                    AccountID, max(ParkingID) AS ID
                FROM
                    Parking
                WHERE
                    AccountID IN (:ids)
                    AND Hidden = 0
                    AND EndDatetime > :date
				GROUP BY AccountID";
                $stmt = $this->dbConnection->executeQuery($sql,
                    ['ids' => array_keys($ids), 'date' => $date],
                    ['ids' => Connection::PARAM_INT_ARRAY, 'date' => \PDO::PARAM_STR]
                );

                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $context->accounts['a' . $row['AccountID']]['HasCurrentTrips'] = true;
                }
            }
        }
    }

    private function loadBlogPosts(Options $options, LoaderContext $context): void
    {
        $blogPostIds = [];

        if (!$options->get(Options::OPTION_LOAD_BLOG_POSTS)) {
            return;
        }

        $idFields = [
            'BlogTagsID',
            'BlogPostID',
            'BlogIdsMileExpiration',
            'BlogIdsPromos',
            'BlogIdsMilesPurchase',
            'BlogIdsMilesTransfers',
        ];
        $nonPostIdFields = ['BlogTagsID'];
        $canSubAccountPost = $options->get(Options::OPTION_LOAD_SUBACCOUNTS) && count($context->accountsIdsWithSubacc);
        $subAccountBlogField = 'BlogIds';

        foreach ($context->accounts as &$account) {
            $account['Blogs'] = ['ids' => []];

            foreach ($idFields as $fieldName) {
                $ids = StringHandler::getIntArrayFromString($account[$fieldName] ?? '');

                if (!empty($ids[0])) {
                    $account['Blogs']['ids'][$fieldName] = $ids;

                    if (!in_array($fieldName, $nonPostIdFields)) {
                        $blogPostIds = array_merge($blogPostIds, $ids);
                    }
                }

                unset($account[$fieldName]);
            }

            if ($account['ProviderID'] && $canSubAccountPost && !empty($account['SubAccountsArray'])) {
                foreach ($account['SubAccountsArray'] as &$subAccount) {
                    $postIds = $this->subAccountMatcher->fetchPostIds(
                        $account['ProviderID'],
                        $subAccount['DisplayName'],
                        $subAccountBlogField
                    );

                    if (!empty($postIds)) {
                        $account['isSubAccountBlogMatched'] = true;
                        $subAccount['Blogs']['ids'][$subAccountBlogField] = $postIds;
                        $blogPostIds = array_merge($blogPostIds, $postIds);
                    }
                }
            }
        }
        $blogPostIds = array_unique($blogPostIds);
        $blogPosts = $this->safeExecutorFactory->make(
            fn () => $this->blogPost->fetchPostById($blogPostIds, false, [
                'fields' => ['id', 'title', 'postURL', 'imageURL'],
            ])
        )->runOrValue([]);

        /*
        if (\random_int(1, 100) < 20) {
            (new ContextAwareLoggerWrapper(getSymfonyContainer()->get('logger')))
            ->withTypedContext()
            ->info(
                'Blog posts debug',
                [
                    'blog_posts_debug' => $blogPosts,
                    'blog_posts_debug_is_null' => \is_null($blogPosts),
                    'blog_posts_debug_ids' => $blogPostIds,
                ]
            );
        }
        */

        if (empty($blogPosts)) {
            return;
        }

        foreach ($context->accounts as &$account) {
            foreach ($idFields as $fieldName) {
                if (empty($account['Blogs']['ids'][$fieldName])
                    || in_array($fieldName, $nonPostIdFields)) {
                    continue;
                }

                foreach ($account['Blogs']['ids'][$fieldName] as $postId) {
                    if (array_key_exists($postId, $blogPosts)) {
                        $account['Blogs'][$fieldName][] = $blogPosts[$postId];
                    }
                }
            }

            if (array_key_exists('isSubAccountBlogMatched', $account)) {
                foreach ($account['SubAccountsArray'] as &$subAccount) {
                    if (empty($subAccount['Blogs']['ids'][$subAccountBlogField])) {
                        continue;
                    }

                    foreach ($subAccount['Blogs']['ids'][$subAccountBlogField] as $postId) {
                        if (array_key_exists($postId, $blogPosts)) {
                            $subAccount['Blogs'][$subAccountBlogField][] = $blogPosts[$postId];
                        }
                    }

                    unset($subAccount['Blogs']['ids']);
                }
            }
        }

        unset($account['Blogs']['ids']);
    }

    private function loadPendingScanData(Options $options, LoaderContext $context): void
    {
        if ($options->get(Options::OPTION_LOAD_PENDING_SCAN_DATA) && sizeof($context->accountsIdsPending) > 0) {
            // leaving it commented, in case we would like to restore this functionality
            //			$sql = "
            //				SELECT
            //					sh.AccountID,
            //					sh.ParsedJson,
            //					sh.Processed,
            //					sh.EmailToken,
            //					sh.EmailDate,
            //					sh.ParsedType,
            //					sh.EmailSubject,
            //					ue.Email AS UserEmail
            //				FROM
            //					ScanHistory sh
            //					JOIN UserEmail ue ON ue.UserEmailID = sh.UserEmailID
            //				WHERE
            //					sh.AccountID IN (".implode(", ", $accountsIdsPending).")
            //			";
            //			$stmt = $this->dbConnection->prepare($sql);
            //			$stmt->execute();
            //			while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            //				$id = $row['AccountID'];
            //				unset($row['AccountID']);
            //				$context->accounts['a' . $id] = array_merge($context->accounts['a' . $id], $row);
            //			}
        }
    }

    private function loadMerchantRecommendations(Options $options, LoaderContext $loadContext)
    {
        if (!$options->get(Options::OPTION_LOAD_MERCHANT_RECOMMENDATIONS)) {
            return;
        }

        if (!$loadContext->providerToAccountIdsListMap) {
            return;
        }

        $providerMerchantIdMap = $this->dbConnection->executeQuery('
               select
                   stat.ProviderID,
                   stat.MerchantID
               from (
               select
                    rpm.ProviderID,
                    first_value(m.MerchantID) over (partition by rpm.ProviderID order by m.Transactions DESC) as MerchantID
                from RetailProviderMerchant rpm
                join Merchant m on rpm.MerchantID = m.MerchantID
                where
                    rpm.ProviderID in (?)
                    and rpm.Manual = 1
                    and rpm.Disabled = 0
                ) stat
                group by stat.ProviderID, stat.MerchantID',
            [\array_keys($loadContext->providerToAccountIdsListMap)],
            [Connection::PARAM_INT_ARRAY]
        )
            ->fetchAllKeyValue();
        $merchantIds = \array_unique(\array_values($providerMerchantIdMap));
        $context = $this->spentAnalyserContextFactory->makeCacheContext();
        $context->setMerchantReportExpectedMultiplier(lazy(fn () => it(
            $this->entityManager
            ->getConnection()
            ->executeQuery(
                "SELECT 
                            MerchantID, 
                            CreditCardID, 
                            CONCAT(sum(Transactions), '_', sum(ExpectedMultiplierTransactions)) as Stat
                        FROM MerchantReport r
                        WHERE Version = ?
                        AND MerchantID in (?)
                        GROUP BY MerchantID, CreditCardID",
                [
                    $context->getCurrentMerchantReportVersion()->getValue(),
                    $merchantIds,
                ],
                [
                    \PDO::PARAM_INT,
                    Connection::PARAM_INT_ARRAY,
                ]
            )
            ->fetchAllAssociative()
        )
            ->reindex(static fn (array $row) => $row['MerchantID'])
            ->collapseByKey()
            ->map(static fn (array $cardsGroup) =>
            it($cardsGroup)
            ->map(static fn (array $row) => [
                $row['CreditCardID'],
                $row['Stat'],
            ])
            ->fromPairs()
            ->toArrayWithKeys()
            )
            ->toArrayWithKeys()
        ));

        $merchantToRecommendationMap = $this->safeExecutorFactory
            ->make(fn () => $this->merchantRecommendationBuilder->build(
                $this->merchantDeepLoader->load($merchantIds),
                $options->get(Options::OPTION_USER),
                $context
            ))
            ->runOrValue([]);

        if (!$merchantToRecommendationMap) {
            return;
        }

        $merchantProvidersListMap =
            it($providerMerchantIdMap)
            ->flip()
            ->collapseByKey()
            ->toArrayWithKeys();

        foreach ($merchantToRecommendationMap as $merchantId => $recommendation) {
            foreach ($merchantProvidersListMap[$merchantId] as $providerId) {
                foreach ($loadContext->providerToAccountIdsListMap[$providerId] as $accountId) {
                    $loadContext->accounts["a{$accountId}"]['MerchantRecommendation'] = $recommendation;
                }
            }
        }
    }

    private function loadProviderCodes(LoaderContext $loaderContext): void
    {
        $this->loadCouponsProviderCodes($loaderContext);
        $this->loadCustomAccountsProviderCodes($loaderContext);
    }

    private function loadCouponsProviderCodes(LoaderContext $loadContext): void
    {
        $couponIdsList =
            it($loadContext->couponsIds)
            ->map(fn ($couponId) => $loadContext->accounts["c{$couponId}"])
            ->filter(fn (array $coupon) => !isset(ProviderCoupon::DOCUMENT_TYPE_TO_KEY_MAP[$coupon['Kind']]))
            ->map(fn (array $coupon) => $coupon['ID'])
            ->toArray();

        if (!$couponIdsList) {
            return;
        }

        /** @var list<array> $customDataList */
        $customDataList = $this->dbConnection->fetchAllAssociative('
            select 
                pc.ProviderCouponID,
                p.Code,
                p.FontColor,
                p.BackgroundColor,
                p.AccentColor,
                p.Border_LM,
                p.Border_DM
            from ProviderCoupon pc
            join Provider p on pc.ProgramName = p.DisplayName
            where pc.ProviderCouponID in (?)',
            [$couponIdsList],
            [Connection::PARAM_INT_ARRAY]
        );

        foreach ($customDataList as $customData) {
            $couponId = $customData['ProviderCouponID'];
            $couponData = &$loadContext->accounts["c{$couponId}"];
            $couponData['CustomAccountProviderCode'] = $customData['Code'];
            $couponData['CustomAccountProviderFontColor'] = $customData['FontColor'];
            $couponData['CustomAccountProviderBackgroundColor'] = $customData['BackgroundColor'];
            $couponData['CustomAccountProviderAccentColor'] = $customData['AccentColor'];
            $couponData['CustomAccountProviderBorder_LM'] = (bool) $customData['Border_LM'];
            $couponData['CustomAccountProviderBorder_DM'] = (bool) $customData['Border_DM'];
        }
        unset($couponData);
    }

    private function loadCustomAccountsProviderCodes(LoaderContext $loadContext): void
    {
        $customAccountIdsList =
            it($loadContext->accountsIds)
            ->map(fn ($accountId) => $loadContext->accounts["a{$accountId}"])
            ->filter(fn (array $account) => null === $account['ProviderID'])
            ->map(fn (array $account) => $account['ID'])
            ->toArray();

        if (!$customAccountIdsList) {
            return;
        }

        $accountIdToCodeMap = $this->dbConnection->fetchAllAssociative('
            select
                a.AccountID,
                p.Code,
                p.FontColor,
                p.BackgroundColor,
                p.AccentColor,
                p.Border_LM,
                p.Border_DM
            from Account a
            join Provider p on a.ProgramName = p.DisplayName
            where a.AccountID in (?)',
            [$customAccountIdsList],
            [Connection::PARAM_INT_ARRAY]
        );

        foreach ($accountIdToCodeMap as $customData) {
            $accountId = $customData['AccountID'];
            $accountData = &$loadContext->accounts["a{$accountId}"];
            $accountData['CustomAccountProviderCode'] = $customData['Code'];
            $accountData['CustomAccountProviderFontColor'] = $customData['FontColor'];
            $accountData['CustomAccountProviderBackgroundColor'] = $customData['BackgroundColor'];
            $accountData['CustomAccountProviderAccentColor'] = $customData['AccentColor'];
            $accountData['CustomAccountProviderBorder_LM'] = (bool) $customData['Border_LM'];
            $accountData['CustomAccountProviderBorder_DM'] = (bool) $customData['Border_DM'];
        }
        unset($accountData);
    }
}

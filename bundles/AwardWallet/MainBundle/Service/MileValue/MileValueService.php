<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\MainBundle\Entity\BalanceWatch;
use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\ProviderMileValue;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\NumberHandler;
use AwardWallet\MainBundle\Service\AccountHistory\Context;
use AwardWallet\MainBundle\Service\ProviderHandler;
use AwardWallet\MainBundle\Service\TransferTimes;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MileValueService implements TranslationContainerInterface
{
    public const PRIMARY_CALC_FIELD = 'AvgPointValue';

    public const INTERNATIONAL_GLOBAL = 1;
    public const INTERNATIONAL_REGIONAL = 0;
    public const CLASSOFSERVICE_ECONOMY = [Constants::CLASS_ECONOMY, Constants::CLASS_BASIC_ECONOMY, Constants::CLASS_ECONOMY_PLUS, Constants::CLASS_PREMIUM_ECONOMY];
    public const CLASSOFSERVICE_BUSINESS = [Constants::CLASS_BUSINESS, Constants::CLASS_FIRST];
    public const AIR_TYPE_CONDITION = [
        'RegionalEconomyMileValue' => ['params' => ['int' => self::INTERNATIONAL_REGIONAL, 'classes' => self::CLASSOFSERVICE_ECONOMY]],
        'RegionalBusinessMileValue' => ['params' => ['int' => self::INTERNATIONAL_REGIONAL, 'classes' => self::CLASSOFSERVICE_BUSINESS]],
        'GlobalEconomyMileValue' => ['params' => ['int' => self::INTERNATIONAL_GLOBAL, 'classes' => self::CLASSOFSERVICE_ECONOMY]],
        'GlobalBusinessMileValue' => ['params' => ['int' => self::INTERNATIONAL_GLOBAL, 'classes' => self::CLASSOFSERVICE_BUSINESS]],
    ];

    public const MIN_VALUE_INPUT_USER = 0;
    public const MAX_VALUE_INPUT_USER = 150;

    private AuthorizationCheckerInterface $authorizationChecker;
    private EntityManagerInterface $entityManager;
    private Connection $connection;
    private LocalizeService $localizeService;
    private TranslatorInterface $translator;
    private AwTokenStorage $tokenStorage;
    private EntityRepository $creditCardRep;
    private UserPointValueService $userPointValueService;
    private LoggerInterface $logger;
    private MileValueCache $mileValueCache;
    private TransferTimes $transferTimes;

    public function __construct(
        EntityManagerInterface $entityManager,
        LocalizeService $localizeService,
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker,
        AwTokenStorage $tokenStorage,
        UserPointValueService $userPointValueService,
        LoggerInterface $logger,
        MileValueCache $mileValueCache,
        TransferTimes $transferTimes
    ) {
        $this->entityManager = $entityManager;
        $this->localizeService = $localizeService;
        $this->translator = $translator;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->creditCardRep = $this->entityManager->getRepository(CreditCard::class);
        $this->userPointValueService = $userPointValueService;

        $this->connection = $entityManager->getConnection();
        $this->logger = $logger;
        $this->mileValueCache = $mileValueCache;
        $this->transferTimes = $transferTimes;
    }

    public function getData(
        bool $convertToOldFormat = true,
        ?array $providerStatuses = null,
        bool $forceUpdate = false
    ): array {
        $cacheKey = MileValueCache::CACHE_KEY . '_full_' . json_encode($providerStatuses);

        $base = $this->mileValueCache->get($cacheKey, function () use ($providerStatuses) {
            $result = [
                ProviderHandler::KIND_KEYS_EXTEND[ProviderHandler::PROVIDER_KIND_TRANSFERS] => [
                    'title' => $this->translator->trans('transferable-points'),
                    'titleTranslateId' => 'transferable-points',
                    'flights' => true,
                    'data' => $this->fetchCombinedTransfersValueData($providerStatuses),
                ],
                ProviderHandler::KIND_KEYS[PROVIDER_KIND_AIRLINE] => [
                    'title' => $this->translator->trans('airline-miles'),
                    'titleTranslateId' => 'airline-miles',
                    'flights' => true,
                    'data' => $this->fetchCombinedMileValueData($providerStatuses),
                ],
                ProviderHandler::KIND_KEYS[PROVIDER_KIND_HOTEL] => [
                    'title' => $this->translator->trans('hotel-points'),
                    'titleTranslateId' => 'hotel-points',
                    'data' => $this->fetchCombinedHotelValueData($providerStatuses),
                ],
                ProviderHandler::KIND_KEYS[PROVIDER_KIND_CREDITCARD] => [
                    'title' => $this->translator->trans('bank-rewards'),
                    'titleTranslateId' => 'bank-rewards',
                    'data' => $this->fetchCombinedBankValueData($providerStatuses),
                ],
                ProviderHandler::KIND_KEYS[PROVIDER_KIND_OTHER] => [
                    'title' => $this->translator->trans('other-rewards'),
                    'titleTranslateId' => 'other-rewards',
                    'data' => [],
                ],
            ];
            $result = $this->cleanProviderWithEmptyValues($result);

            return $this->extractRangeValuesForTransfers($result);
        }, $forceUpdate);

        $userset = null !== $this->tokenStorage->getToken() && $this->authorizationChecker->isGranted('ROLE_USER')
            ? $this->userPointValueService->getUserSetValues($this->tokenStorage->getBusinessUser())
            : [];

        $result = $this->mergeData(is_array($base) ? $base : [], $userset);
        $result = $this->flyingBlueMerge($result, true);

        if ($convertToOldFormat) {
            $result = $this->convertToArrayFormat($result);

            foreach ($result as $type => $data) {
                if (empty($data['data'])) {
                    unset($result[$type]);
                }
            }
        }

        return $result;
    }

    public function getBankPointsShortData(bool $onlyTransfers = false, bool $isOnlyEarningList = false): array
    {
        $allData = $this->getData(false);
        $transfers = array_key_exists(ProviderHandler::KIND_KEYS_EXTEND[ProviderHandler::PROVIDER_KIND_TRANSFERS], $allData)
            ? $allData[ProviderHandler::KIND_KEYS_EXTEND[ProviderHandler::PROVIDER_KIND_TRANSFERS]]
            : ['data' => []];
        $banks = array_key_exists(ProviderHandler::KIND_KEYS[PROVIDER_KIND_CREDITCARD], $allData)
            ? $allData[ProviderHandler::KIND_KEYS[PROVIDER_KIND_CREDITCARD]]
            : ['data' => []];

        $toMap = $onlyTransfers
            ? array_values($transfers['data'])
            : array_merge(
                array_values($transfers['data']),
                array_values($banks['data'])
            );

        if ($isOnlyEarningList) {
            /** @var ProviderMileValueItem $item */
            foreach ($toMap as $key => $item) {
                if (!in_array($item->getProviderId(), Provider::EARNING_POTENTIAL_LIST, true)) {
                    unset($toMap[$key]);
                }
            }
            $toMap = array_values($toMap);
        }

        return array_map(
            static fn (ProviderMileValueItem $item) => [
                'id' => $item->getProviderId(),
                'name' => $item->getShortName(),
                'autoValue' => $item->getAutoValues()[self::PRIMARY_CALC_FIELD]['value'] ?? 0,
                'manualValue' => $item->getManualValues()[self::PRIMARY_CALC_FIELD]['value'] ?? 0,
                'userValue' => $item->getUserValues()[self::PRIMARY_CALC_FIELD]['value'] ?? 0,
                'showValue' => $item->getPrimaryValue(self::PRIMARY_CALC_FIELD),
                'minValue' => $item->getMinValue(self::PRIMARY_CALC_FIELD),
                'maxValue' => $item->getMaxValue(self::PRIMARY_CALC_FIELD),
                'currency' => ProviderMileValueItem::CURRENCY_CENT,
            ],
            $toMap
        );
    }

    public function getProviderItem(int $providerId, ?array $cachedMileValueData = null): ?array
    {
        foreach ($cachedMileValueData ?? $this->getData() as $type => $items) {
            if (\array_key_exists($providerId, $items['data'])) {
                if (!empty($items['data'][$providerId]['subBrands'])) {
                    $items['data'][$providerId]['subBrands'] = array_values($items['data'][$providerId]['subBrands']);
                }

                return $items['data'][$providerId] + ['group' => $type];
            }
        }

        return null;
    }

    public function getProviderMileValueItem(int $providerId): ?ProviderMileValueItem
    {
        foreach ($this->getData(false) as $items) {
            if (\array_key_exists($providerId, $items['data'])) {
                return $items['data'][$providerId];
            }
        }

        return null;
    }

    public function getProviderValue(int $providerId, string $key, bool $isCalculatedOnly = true): float
    {
        foreach ($this->getData(false) as $items) {
            if (\array_key_exists($providerId, $items['data'])) {
                return ($isCalculatedOnly && $items['data'][$providerId]->isUserPrimary($key))
                    ? $items['data'][$providerId]->getSecondaryValue($key)
                    : $items['data'][$providerId]->getPrimaryValue($key);
            }
        }

        throw new \Exception('Provider ' . $providerId . ' with "' . $key . '" is not defined in MileValueService');
    }

    public function fetchCombinedTransfersValueData(?array $providerStatuses = null): array
    {
        $datas = [
            'AvgPointValue' => [],
            'RegionalEconomyMileValue' => [],
            'RegionalBusinessMileValue' => [],
            'GlobalEconomyMileValue' => [],
            'GlobalBusinessMileValue' => [],
        ];

        if (empty($providerStatuses)) {
            $providerStatuses = [ProviderMileValue::STATUS_ENABLED];
        }
        $banks = $this->connection->executeQuery('
            SELECT
                    p.ProviderID, p.DisplayName, p.ShortName, p.Code, p.Kind,
                    pmv.RegionalEconomyMileValue, pmv.RegionalBusinessMileValue, pmv.GlobalEconomyMileValue, pmv.GlobalBusinessMileValue, pmv.AvgPointValue, pmv.Status
            FROM ProviderMileValue pmv
            JOIN Provider p ON (p.ProviderID = pmv.ProviderID AND p.Kind = :kind)
            WHERE
                    pmv.ProviderID IN (:transferProviderId)
                AND pmv.EndDate IS NULL
                AND pmv.Status IN (:status)
            ORDER BY p.DisplayName',
            [
                'transferProviderId' => $this->getTransfersProviderId(),
                'kind' => PROVIDER_KIND_CREDITCARD,
                'status' => $providerStatuses,
            ],
            [
                'transferProviderId' => Connection::PARAM_INT_ARRAY,
                'kind' => \PDO::PARAM_INT,
                'status' => Connection::PARAM_INT_ARRAY,
            ]
        )->fetchAllAssociative();

        foreach ($banks as $bank) {
            $providerId = $bank['ProviderID'];
            $transferPartners = $this->connection->executeQuery('
                SELECT
                        ts.TargetProviderID, ts.SourceRate, ts.TargetRate
                FROM TransferStat ts
                WHERE
                        ts.SourceRate IS NOT NULL 
                    AND ts.TargetRate IS NOT NULL
                    AND ts.SourceProviderID = ?',
                [$providerId],
                [\PDO::PARAM_INT]
            )->fetchAllAssociative();
            $transferPartners = array_combine(array_column($transferPartners, 'TargetProviderID'), $transferPartners);
            $transferPartnersId = array_keys($transferPartners);

            $blankValueKeys = [
                'ProviderID' => $providerId,
                'Code' => $bank['Code'],
                'Kind' => $bank['Kind'],
                'DisplayName' => $bank['DisplayName'],
                'ShortName' => $bank['ShortName'],
                'CertificationDate' => null,
                '_count' => 0,
                'sumSpent' => 0,
                'sumTaxesSpent' => 0,
                'sumAlternativeCost' => 0,
                'RegionalEconomyMileValue' => $bank['RegionalEconomyMileValue'],
                'RegionalBusinessMileValue' => $bank['RegionalBusinessMileValue'],
                'GlobalEconomyMileValue' => $bank['GlobalEconomyMileValue'],
                'GlobalBusinessMileValue' => $bank['GlobalBusinessMileValue'],
                'AvgPointValue' => $bank['AvgPointValue'],
                'Status' => $bank['Status'],
            ];

            // AvgValue
            $mileValueDatas = $this->getAvgValueData(
                false,
                true,
                ' AND p.ProviderID IN(:partnerIds)',
                ['partnerIds' => $transferPartnersId],
                ['partnerIds' => Connection::PARAM_INT_ARRAY],
                $providerStatuses
            );
            $datas['AvgPointValue'][$providerId] = $this->calculateTransferPartners(
                $blankValueKeys,
                $mileValueDatas,
                $transferPartners
            );
            // /AvgValue

            foreach (self::AIR_TYPE_CONDITION as $valueKey => $options) {
                $mileValueDatas = $this->getMileValueData(
                    false,
                    true,
                    ' AND p.ProviderID IN(:partnerIds) AND v.International = :int AND v.ClassOfService IN (:classes) AND p.Kind = :kind',
                    array_merge($options['params'], [
                        'kind' => PROVIDER_KIND_AIRLINE,
                        'partnerIds' => $transferPartnersId,
                    ]),
                    [
                        'int' => \PDO::PARAM_INT,
                        'classes' => Connection::PARAM_STR_ARRAY,
                        'kind' => \PDO::PARAM_INT,
                        'partnerIds' => Connection::PARAM_INT_ARRAY,
                    ],
                    $providerStatuses
                );
                $datas[$valueKey][$providerId] = $this->calculateTransferPartners(
                    $blankValueKeys,
                    $mileValueDatas,
                    $transferPartners
                );
            }
        }

        return $this->dataMapping($datas);
    }

    public function fetchCombinedMileValueData(?array $providerStatuses = null, array $extra = [], bool $past = false, bool $certifiedOnly = true): array
    {
        $cacheKey = MileValueCache::CACHE_KEY . '_mile_' . json_encode($providerStatuses) . "_" . json_encode($extra) . '_' . json_encode($past) . '_' . json_encode($certifiedOnly);

        return $this->mileValueCache->get($cacheKey, function () use ($providerStatuses, $extra, $past, $certifiedOnly) {
            $where = " and v.International = :int AND v.ClassOfService IN (:classes) and p.Kind = :kind";
            $paramTypes = ["int" => \PDO::PARAM_INT, "classes" => Connection::PARAM_STR_ARRAY, "kind" => \PDO::PARAM_INT];

            $mileValue = [
                'AvgPointValue' => $this->getAvgValueData($past, $certifiedOnly, ' AND p.Kind = :kind', ['kind' => PROVIDER_KIND_AIRLINE], $paramTypes, $providerStatuses, $extra),
                'RegionalEconomyMileValue' => $this->getMileValueData($past, $certifiedOnly, $where, ["int" => self::INTERNATIONAL_REGIONAL, "classes" => self::CLASSOFSERVICE_ECONOMY, "kind" => PROVIDER_KIND_AIRLINE], $paramTypes, $providerStatuses, $extra),
                'RegionalBusinessMileValue' => $this->getMileValueData($past, $certifiedOnly, $where, ["int" => self::INTERNATIONAL_REGIONAL, "classes" => self::CLASSOFSERVICE_BUSINESS, "kind" => PROVIDER_KIND_AIRLINE], $paramTypes, $providerStatuses, $extra),
                'GlobalEconomyMileValue' => $this->getMileValueData($past, $certifiedOnly, $where, ["int" => self::INTERNATIONAL_GLOBAL, "classes" => self::CLASSOFSERVICE_ECONOMY, "kind" => PROVIDER_KIND_AIRLINE], $paramTypes, $providerStatuses, $extra),
                'GlobalBusinessMileValue' => $this->getMileValueData($past, $certifiedOnly, $where, ["int" => self::INTERNATIONAL_GLOBAL, "classes" => self::CLASSOFSERVICE_BUSINESS, "kind" => PROVIDER_KIND_AIRLINE], $paramTypes, $providerStatuses, $extra),
            ];

            return $this->dataMapping($mileValue);
        });
    }

    /**
     * @return ProviderMileValueItem[]
     */
    public function fetchCombinedHotelValueData(?array $providerStatuses = null, array $extra = [], bool $past = false, bool $certifiedOnly = true): array
    {
        $cacheKey = MileValueCache::CACHE_KEY . '_hotel_' . json_encode($providerStatuses) . "_" . json_encode($extra) . '_' . json_encode($past) . '_' . json_encode($certifiedOnly);

        return $this->mileValueCache->get($cacheKey, function () use ($providerStatuses, $extra, $past, $certifiedOnly) {
            $hotelValue = [
                'AvgPointValue' => $this->getHotelValueData($past, $certifiedOnly, " and p.Kind = :kind", ["kind" => PROVIDER_KIND_HOTEL], ["kind" => \PDO::PARAM_INT], $providerStatuses, $extra),
            ];

            return $this->dataMapping($hotelValue);
        });
    }

    public function getFlatDataList(bool $convertToOldFormat = true): array
    {
        $datas = $this->getData($convertToOldFormat);

        if ($convertToOldFormat) {
            foreach ($datas as $type => $data) {
                $datas[$type]['data'] = array_values($data['data']);

                foreach ($datas[$type]['data'] as $providerId => $items) {
                    if (!empty($items['subBrands'])) {
                        $datas[$type]['data'][$providerId]['subBrands'] = array_values($datas[$type]['data'][$providerId]['subBrands']);
                    }
                }
            }
        }

        return $datas;
    }

    public function getFlatDataListById(): array
    {
        $result = [];
        $datas = $this->getData(false);

        foreach ($datas as $type => $data) {
            /** @var ProviderMileValueItem $provider */
            foreach ($data['data'] as $provider) {
                $provider->type = $type;
                $result[$provider->getProviderId()] = $provider;
            }
        }

        return $result;
    }

    public function calculateCashEquivalent(float $pointValue, float $balance, ?int $precision = 0): array
    {
        $cashEqValue = ($balance * $pointValue) / 100;

        if (null === $precision) {
            if ($cashEqValue < 0.0099) {
                $precision = 4;
            } elseif ($cashEqValue < 10) {
                $precision = 2;
            } else {
                $precision = 0;
            }
            $cashEqValue = NumberHandler::numberPrecision($cashEqValue, $precision);
        } elseif ($cashEqValue > 1) {
            $cashEqValue = round($cashEqValue, $precision);
        } elseif ($cashEqValue >= 0.0099) {
            $cashEqValue = round($cashEqValue, 2, PHP_ROUND_HALF_DOWN);
        } else {
            $cashEqValue = round($cashEqValue, 4, PHP_ROUND_HALF_DOWN);
        }

        $cashEqValueStr = (string) $cashEqValue;

        if (0 === (int) substr(strrchr($cashEqValueStr, '.'), 1)) {
            $precision = 0;
        }

        return [
            'raw' => $cashEqValue,
            'formatted' => $this->localizeService->formatCurrency(
                $cashEqValue,
                'USD',
                0 === $precision,
                $this->tokenStorage->getUser() ? $this->tokenStorage->getUser()->getLocale() : null
            ),
        ];
    }

    public function getTransferableProviders(?int $sourceProviderId = null, ?int $targetProviderId = null): array
    {
        $cacheKey = 'mv_transferable_providers_v2_' . ($sourceProviderId ?: '_0') . ($targetProviderId ?: '_0');

        return $this->mileValueCache->get($cacheKey, function () use ($sourceProviderId, $targetProviderId) {
            $items = $this->transferTimes->getData(
                BalanceWatch::POINTS_SOURCE_TRANSFER,
                null,
                null,
                ['isUSOnly' => true]
            )['data'] ?? [];
            $list = [];

            foreach ($items as $item) {
                if (empty($item['SourceRate']) || empty($item['TargetRate'])) {
                    continue;
                }

                $sourceProviderId = (int) $item['SourceProviderID'];
                $targetProviderId = (int) $item['TargetProviderID'];

                if (!array_key_exists($sourceProviderId, $list)) {
                    $list[$sourceProviderId] = [];
                }

                $list[$sourceProviderId][$targetProviderId] = $item;
            }

            return $list;
        });
    }

    public static function isValidValue($value): bool
    {
        if (is_numeric($value) && $value >= self::MIN_VALUE_INPUT_USER && $value <= self::MAX_VALUE_INPUT_USER) {
            return true;
        }

        return false;
    }

    public static function getTranslationMessages(): array
    {
        return [
            (new Message('value-of'))->setDesc('Value of %name% %currency%'),
            (new Message('program-miles'))->setDesc('%name% miles'),
            (new Message('program-points'))->setDesc('%name% points'),
            (new Message('approximate-value'))->setDesc('Approximate Value'),
            (new Message('awardWallet-estimate'))->setDesc('AwardWallet Estimate'),
            (new Message('personal-redemptions'))->setDesc('Personal Redemptions'),
            (new Message('based-avg-value-per-point'))->setDesc('Based on an average value of %value% per point (%count% bookings)'),
            (new Message('based-aw-users-redemptions'))->setDesc("Based on the AwardWallet users' redemptions of %name% %currency%"),
            (new Message('based-your-personal-redemption'))->setDesc('Based on your personal redemptions of %name% %valueType%'),
            (new Message('personally-set-average'))->setDesc('Personally Set Average'),
            (new Message('based-on-last-bookings'))->setDesc('Based on the last %number% bookings%as-of-date%'),
            (new Message('as-of-date'))->setDesc('as of %date%'),
            (new Message('not-enough-data'))->setDesc('Not enough data'),
            (new Message('override'))->setDesc('Override'),
            (new Message('transferable-points'))->setDesc('Transferable Points'),
            (new Message('airline-miles'))->setDesc('Airline Miles'),
            (new Message('hotel-points'))->setDesc('Hotel Points'),
            (new Message('bank-rewards'))->setDesc('Bank Rewards'),
            (new Message('other-rewards'))->setDesc('Other Rewards'),
        ];
    }

    private function convertToArrayFormat(array $list): array
    {
        foreach ($list as $category => &$datas) {
            $datas['data'] = $this->convertDataToArrayFormat($datas['data']);
        }

        return $list;
    }

    private function convertDataToArrayFormat(array $datas): array
    {
        $valuesConvert = static function ($values): array {
            $point = [];

            foreach ($values as $valueKey => $base) {
                foreach ($base as $k => $val) {
                    if (in_array($k, ['count', 'sumSpent', 'currency'])) {
                        $point[$valueKey . '_' . $k] = $val;
                    } elseif ('value' === $k) {
                        $point[$valueKey] = $val;
                    }
                }
            }

            return $point;
        };

        $arr = [];

        /** @var ProviderMileValueItem $item */
        foreach ($datas as $key => $item) {
            $point = [
                'ProviderID' => $item->getProviderId(),
                'Code' => $item->getCode(),
                'DisplayName' => $item->getName(),
                'ShortName' => $item->getShortName(),
                'CertifyDate' => $item->getCertifyDate(),
            ];

            if (!empty($auto = $item->getAutoValues())) {
                if (!empty($values = $valuesConvert($auto))) {
                    $point['auto'] = $values;
                }
            }

            if (!empty($manual = $item->getManualValues())) {
                if (!empty($values = $valuesConvert($manual))) {
                    $point['manual'] = $values;
                }
            }

            if (!empty($user = $item->getUserValues())) {
                if (!empty($values = $valuesConvert($user))) {
                    $point['user'] = $values;
                }
            }

            foreach (ProviderMileValueItem::LIST_KEY_VALUES as $keyData) {
                $sourceKey = array_key_exists('user', $point)
                    ? 'user'
                    : 'auto';

                if (!array_key_exists($sourceKey, $point)
                    || !array_key_exists($keyData, $point[$sourceKey])
                    || !self::isValidValue($point[$sourceKey][$keyData])
                ) {
                    continue;
                }

                $point['show'][$keyData] = $point[$sourceKey][$keyData];
                $point['show'][$keyData . '_count'] = $point[$sourceKey][$keyData . '_count'] ?? null;
                $point['show'][$keyData . '_sumSpent'] = $point[$sourceKey][$keyData . '_sumSpent'] ?? null;
                $point['show'][$keyData . '_currency'] = $point[$sourceKey][$keyData . '_currency'] ?? null;

                if ((int) $point['show'][$keyData . '_count'] < ProviderMileValueItem::MIN_COUNT && 'user' !== $sourceKey) {
                    $point['show'][$keyData] = null;
                }

                if (!empty($subBrands = $item->getSubBrands())) {
                    $point['subBrands'] = $this->convertDataToArrayFormat($subBrands);
                }
            }

            $arr[$item->getProviderId()] = $point;
        }

        return $arr;
    }

    private function mergeData(array $base, array $userset): array
    {
        foreach ($userset as $providerId => $row) {
            foreach ($base as $type => &$items) {
                if (array_key_exists($providerId, $items['data'])) {
                    $items['data'][$providerId]->setUserValues($row['user']);
                    unset($userset[$providerId]);

                    break;
                }
            }
        }

        if (!empty($userset)) {
            foreach ($userset as $providerId => $row) {
                $mileValueItem = new ProviderMileValueItem();
                $mileValueItem->setProviderId($providerId);
                $mileValueItem->setKind($row['Kind'] ?? null);
                $mileValueItem->setCode($row['Code'] ?? '');
                $mileValueItem->setName($row['DisplayName']);
                $mileValueItem->setUserValues($row['user']);

                switch ($row['Kind']) {
                    case PROVIDER_KIND_AIRLINE:
                        $key = 'airlines';

                        break;

                    case PROVIDER_KIND_HOTEL:
                        $key = 'hotels';

                        break;

                    case PROVIDER_KIND_CREDITCARD:
                        $key = 'banks';

                        break;

                    default:
                        $key = 'others';

                        break;
                }

                $base[$key]['data'][$providerId] = $mileValueItem;
            }
        }

        foreach ($base as $type => &$items) {
            uasort($items['data'], static fn ($a, $b) => strcmp($a->getName(), $b->getName()));
            /*
            uasort($items['data'], static function($a, $b) {
                $valA = $a->getUserValues()['AvgPointValue']['value'] ?? $a->getManualValues()['AvgPointValue']['value'] ?? $a->getAutoValues()['AvgPointValue']['value'];
                $valB = $b->getUserValues()['AvgPointValue']['value'] ?? $b->getManualValues()['AvgPointValue']['value'] ?? $b->getAutoValues()['AvgPointValue']['value'];

                return $valB <=> $valA;
            });
            */
        }

        return $base;
    }

    private function calculateTransferPartners(array $values, array $datas, array $transferPartners): array
    {
        foreach ($datas as $providerId => $item) {
            if (!array_key_exists($providerId, $transferPartners) || 0 === (int) $item['_count']) {
                continue;
            }

            if (0 === (int) $transferPartners[$providerId]['SourceRate'] || 0 === (int) $transferPartners[$providerId]['TargetRate']) {
                $this->logger->warning("zero SourceRate: $providerId");

                continue;
            }

            $values['_count'] += $item['_count'];
            $values['sumAlternativeCost'] += $item['sumAlternativeCost'];
            $values['sumTaxesSpent'] += $item['sumTaxesSpent'];

            $ratio = round($transferPartners[$providerId]['TargetRate'] / $transferPartners[$providerId]['SourceRate'], 2);
            $values['sumSpent'] += ($item['sumSpent'] / $ratio);
        }

        return $values;
    }

    private function getAvgValueData(
        bool $past,
        bool $certifiedOnly,
        string $where,
        array $params,
        array $paramTypes,
        ?array $providerStatuses = null,
        array $extra = []
    ): array {
        [$groupByField, $where, $joinMileValueFilter] = $this->getSqlFields($past, $certifiedOnly, $where, $providerStatuses, $extra);

        $rows = $this->connection->fetchAllAssociative('
            SELECT
                COUNT(v.MileValueID) as _count,
                ROUND(SUM(v.TotalMilesSpent), 4) as sumSpent, 
                ROUND(SUM(v.TotalTaxesSpent), 4) as sumTaxesSpent, 
                ROUND(SUM(v.AlternativeCost), 4) as sumAlternativeCost,
                ' . $groupByField . ',
                pmv.ProviderID, 
                p.DisplayName, p.Code, p.Kind,
                pmv.CertificationDate,
                pmv.RegionalEconomyMileValue, pmv.RegionalBusinessMileValue, pmv.GlobalEconomyMileValue, pmv.GlobalBusinessMileValue, pmv.AvgPointValue, pmv.Status
            FROM ProviderMileValue pmv 
            JOIN Provider p ON (p.ProviderID = pmv.ProviderID)
            LEFT JOIN MileValue v ON 
                pmv.ProviderID = v.ProviderID 
                AND v.Status NOT IN (:excludedStatuses)
                AND (
                        v.TotalMilesSpent > 0
                    -- AND v.TotalTaxesSpent > 0
                    AND v.AlternativeCost > 0
                )
                AND ' . $joinMileValueFilter . '
            WHERE
                ' . $where . '
            GROUP BY 
                ' . $groupByField . ',
                pmv.ProviderID,
                p.DisplayName, 
                pmv.CertificationDate, pmv.RegionalEconomyMileValue, pmv.RegionalBusinessMileValue, pmv.GlobalEconomyMileValue, pmv.GlobalBusinessMileValue, pmv.AvgPointValue, pmv.Status
            ORDER BY p.DisplayName',
            array_merge($params, ['excludedStatuses' => CalcMileValueCommand::EXCLUDED_STATUSES]),
            array_merge($paramTypes, ['kind' => \PDO::PARAM_INT, 'excludedStatuses' => Connection::PARAM_STR_ARRAY])
        );

        return array_combine(array_column($rows, preg_replace('#^\w+\.#ims', '', $groupByField)), $rows);
    }

    private function getSqlFields(bool $past, bool $certifiedOnly, string $where, ?array $providerStatuses = null, array $extra = []): array
    {
        $where = $this->getExtraWhere($extra, $providerStatuses) . $where;

        if ($past) {
            $groupByField = 'pmv.ProviderMileValueID';
            $where .= ' AND pmv.StartDate IS NOT NULL AND pmv.EndDate IS NOT NULL';
            $joinMileValueFilter = "v.CreateDate >= pmv.StartDate AND v.CreateDate < ADDDATE(pmv.EndDate, 1)";
        } else {
            $groupByField = 'v.ProviderID';
            $where .= ' AND pmv.EndDate IS NULL';
            $joinMileValueFilter = '(v.CreateDate >= pmv.StartDate OR pmv.StartDate IS NULL)';

            if ($certifiedOnly) {
                $joinMileValueFilter .= ' AND (v.CreateDate <= ADDDATE(pmv.CertificationDate, 1) OR pmv.CertificationDate IS NULL)';
            }
        }

        return [$groupByField, $where, $joinMileValueFilter];
    }

    private function getMileValueData(bool $past, bool $certifiedOnly, string $where, array $params, array $paramTypes, ?array $providerStatuses = null, array $extra = []): array
    {
        [$groupByField, $where, $joinMileValueFilter] = $this->getSqlFields($past, $certifiedOnly, $where, $providerStatuses, $extra);

        $rows = $this->connection->fetchAllAssociative('
            SELECT
                COUNT(v.MileValueID) as _count,
                ROUND(SUM(v.TotalMilesSpent), 2) as sumSpent, 
                ROUND(SUM(v.TotalTaxesSpent), 2) as sumTaxesSpent, 
                ROUND(SUM(v.AlternativeCost), 2) as sumAlternativeCost,
                ' . $groupByField . ',
                pmv.ProviderID, 
                p.DisplayName, p.Code, p.Kind,
                pmv.CertificationDate, 
                pmv.RegionalEconomyMileValue, 
                pmv.RegionalBusinessMileValue, 
                pmv.GlobalEconomyMileValue, 
                pmv.GlobalBusinessMileValue, 
                pmv.AvgPointValue,
                pmv.Status
            FROM ProviderMileValue pmv 
            JOIN Provider p on p.ProviderID = pmv.ProviderID
            LEFT JOIN MileValue v ON 
                pmv.ProviderID = v.ProviderID 
                AND v.Status NOT IN (:excludedStatuses)
                AND (
                        v.TotalMilesSpent > 0
                    -- AND v.TotalTaxesSpent > 0
                    AND v.AlternativeCost > 0
                )
                AND ' . $joinMileValueFilter . '
            WHERE
                ' . $where . '
            GROUP BY 
                ' . $groupByField . ', 
                pmv.ProviderID,
                p.DisplayName,
                pmv.CertificationDate, 
                pmv.RegionalEconomyMileValue, 
                pmv.RegionalBusinessMileValue, 
                pmv.GlobalEconomyMileValue, 
                pmv.GlobalBusinessMileValue, 
                pmv.AvgPointValue,
                pmv.Status
            ORDER BY p.DisplayName',
            array_merge($params, ["excludedStatuses" => CalcMileValueCommand::EXCLUDED_STATUSES]),
            array_merge($paramTypes, ["kind" => \PDO::PARAM_INT, "excludedStatuses" => Connection::PARAM_STR_ARRAY])
        );

        $result = array_combine(array_column($rows, preg_replace('#^\w+\.#ims', '', $groupByField)), $rows);

        return $result;
    }

    private function getHotelValueData(bool $past, bool $certifiedOnly, string $where, array $params, array $paramTypes, ?array $providerStatuses = null, array $extra = []): array
    {
        [$groupByField, $where, $joinMileValueFilter] = $this->getSqlFields($past, $certifiedOnly, $where, $providerStatuses, $extra);

        $rows = $this->connection->fetchAllAssociative('
            SELECT
                COUNT(v.HotelPointValueID) as _count,
                ROUND(SUM(v.TotalPointsSpent), 2) as sumSpent, 
                ROUND(SUM(v.TotalTaxesSpent), 2) as sumTaxesSpent, 
                ROUND(SUM(v.AlternativeCost), 2) as sumAlternativeCost,
                ' . $groupByField . ',
                p.DisplayName, p.Code, p.Kind,
                pmv.ProviderID, pmv.CertificationDate, pmv.RegionalEconomyMileValue, pmv.RegionalBusinessMileValue, pmv.GlobalEconomyMileValue, pmv.GlobalBusinessMileValue, pmv.AvgPointValue, pmv.Status
            FROM ProviderMileValue pmv 
            JOIN Provider p ON (p.ProviderID = pmv.ProviderID)
            LEFT JOIN HotelPointValue v ON (
                        pmv.ProviderID = v.ProviderID
                    AND v.Status NOT IN (:excludedStatuses)
                    AND (v.TotalPointsSpent > 0 AND v.AlternativeCost > 0 AND v.PointValue > 0)
                    AND ' . $joinMileValueFilter . '
                )
            WHERE
                ' . $where . '
            GROUP BY 
                ' . $groupByField . ', 
                pmv.ProviderID,
                p.DisplayName,
                pmv.CertificationDate, pmv.RegionalEconomyMileValue, pmv.RegionalBusinessMileValue, pmv.GlobalEconomyMileValue, pmv.GlobalBusinessMileValue, pmv.AvgPointValue, pmv.Status
            ORDER BY p.DisplayName',
            array_merge($params, ['kind' => PROVIDER_KIND_HOTEL, "excludedStatuses" => CalcMileValueCommand::EXCLUDED_STATUSES]),
            array_merge($paramTypes, ["kind" => \PDO::PARAM_INT, "excludedStatuses" => Connection::PARAM_STR_ARRAY])
        );

        $result = array_combine(array_column($rows, preg_replace('#^\w+\.#ims', '', $groupByField)), $rows);

        $hotelBrands = $this->getHotelBrandValueData($past, $certifiedOnly, $where, $params, $paramTypes, $providerStatuses, $extra);

        foreach ($result as $providerId => &$provider) {
            foreach ($hotelBrands as $brand) {
                if ($brand['ProviderID'] == $providerId) {
                    if (!array_key_exists('brands', $provider)) {
                        $provider['brands'] = [];
                    }

                    $provider['brands'][] = $brand;
                }
            }
        }

        return $result;
    }

    private function getHotelBrandValueData(bool $past, bool $certifiedOnly, string $where, array $params, array $paramTypes, ?array $providerStatuses = null, array $extra = []): array
    {
        [$groupByField, $where, $joinMileValueFilter] = $this->getSqlFields($past, $certifiedOnly, $where, $providerStatuses, $extra);

        $brands = $this->connection->fetchAllAssociative('
            SELECT
                COUNT(v.HotelPointValueID) as _count,
                ROUND(SUM(v.TotalPointsSpent), 2) as sumSpent, 
                ROUND(SUM(v.TotalTaxesSpent), 2) as sumTaxesSpent, 
                ROUND(SUM(v.AlternativeCost), 2) as sumAlternativeCost,
                ' . $groupByField . ',
                pmv.ProviderID, pmv.CertificationDate, pmv.AvgPointValue,
                hb.HotelBrandID, hb.Name
            FROM ProviderMileValue pmv 
            LEFT JOIN HotelPointValue v ON (
                        pmv.ProviderID = v.ProviderID
                    AND v.Status NOT IN (:excludedStatuses)
                    AND (v.TotalPointsSpent > 0 AND v.AlternativeCost > 0 AND v.PointValue > 0)
                    AND ' . $joinMileValueFilter . '
                )
            JOIN HotelBrand hb ON (hb.HotelBrandID = v.BrandID)
            WHERE
                    pmv.Status IN(1)
                AND pmv.EndDate IS NULL
            GROUP BY 
                ' . $groupByField . ', 
                v.BrandID,
                pmv.ProviderID, pmv.CertificationDate, pmv.AvgPointValue
            ORDER BY hb.Name ASC
        ',
            array_merge($params, ['kind' => PROVIDER_KIND_HOTEL, "excludedStatuses" => CalcMileValueCommand::EXCLUDED_STATUSES]),
            array_merge($paramTypes, ["kind" => \PDO::PARAM_INT, "excludedStatuses" => Connection::PARAM_STR_ARRAY])
        );

        return $brands;
    }

    private function getExtraWhere(array $extra, ?array $providerStatuses): string
    {
        if (empty($providerStatuses)) {
            $providerStatuses = [ProviderMileValue::STATUS_ENABLED];
        }

        $result = "pmv.Status IN(" . implode(", ", $providerStatuses) . ")";

        if (!empty($extra['transferTargetProviderIds'])) {
            $result .= 'and v.ProviderID IN(' . implode(',', $extra['transferTargetProviderIds']) . ')';
        }

        return $result;
    }

    private function fetchCombinedBankValueData(?array $providerStatuses = null): array
    {
        if (empty($providerStatuses)) {
            $providerStatuses = [ProviderMileValue::STATUS_ENABLED];
        }
        $banks = $this->connection->executeQuery(
            '
            SELECT
                    p.ProviderID, p.DisplayName, p.ShortName, p.Code, p.Kind,
                    pmv.RegionalEconomyMileValue, pmv.RegionalBusinessMileValue, pmv.GlobalEconomyMileValue, pmv.GlobalBusinessMileValue, pmv.AvgPointValue, pmv.Status
            FROM ProviderMileValue pmv
            JOIN Provider p ON (p.ProviderID = pmv.ProviderID AND p.Kind = :kind)
            WHERE
                    pmv.ProviderID NOT IN (:transferProviderId)
                AND pmv.EndDate IS NULL
                AND pmv.Status IN (:status)
            ORDER BY p.DisplayName
            ',
            ['transferProviderId' => $this->getTransfersProviderId(), 'kind' => PROVIDER_KIND_CREDITCARD, 'status' => $providerStatuses],
            ['transferProviderId' => Connection::PARAM_INT_ARRAY, 'kind' => \PDO::PARAM_INT, 'status' => Connection::PARAM_INT_ARRAY]
        )->fetchAllAssociative();

        $result = [];

        foreach ($banks as $bank) {
            $providerId = $bank['ProviderID'];
            $result['AvgPointValue'][$providerId] = [
                'ProviderID' => $providerId,
                'Code' => $bank['Code'],
                'Kind' => $bank['Kind'],
                'DisplayName' => $bank['DisplayName'],
                'ShortName' => $bank['ShortName'],
                'CertificationDate' => null,
                '_count' => 0,
                'sumSpent' => 0,
                'sumTaxesSpent' => 0,
                'sumAlternativeCost' => 0,
                'RegionalEconomyMileValue' => $bank['RegionalEconomyMileValue'],
                'RegionalBusinessMileValue' => $bank['RegionalBusinessMileValue'],
                'GlobalEconomyMileValue' => $bank['GlobalEconomyMileValue'],
                'GlobalBusinessMileValue' => $bank['GlobalBusinessMileValue'],
                'AvgPointValue' => $bank['AvgPointValue'],
                'Status' => $bank['Status'],
            ];
        }

        return $this->dataMapping($result);
    }

    private function dataMapping(array $tableValues): array
    {
        $tableValues = $this->flyingBlueMerge($tableValues, false);

        $providerItems = [];
        $manualOverrideValues = $tableValues[self::PRIMARY_CALC_FIELD] ?? [];

        foreach ($tableValues as $keyData => $data) {
            foreach ($data as $providerId => $item) {
                $mileValueItem = array_key_exists($providerId, $providerItems)
                    ? $providerItems[$providerId]
                    : new ProviderMileValueItem();
                $mileValueItem->extractItemData($keyData, $item);
                $providerItems[$providerId] = $mileValueItem;
            }
        }

        // Manual Values set from ProviderMileValue table
        foreach ($manualOverrideValues as $providerId => $item) {
            foreach (ProviderMileValueItem::LIST_KEY_VALUES as $keyData) {
                if (self::PRIMARY_CALC_FIELD === $keyData) {
                    continue;
                }

                $mileValueItem = array_key_exists($providerId, $providerItems)
                    ? $providerItems[$providerId]
                    : new ProviderMileValueItem();

                if (!$mileValueItem->isManualValuesExists($keyData)
                    && !empty($manualOverrideValues[$providerId][$keyData])) {
                    $mileValueItem->extractItemData($keyData, $item);
                    $providerItems[$providerId] = $mileValueItem;
                }
            }
        }

        foreach ($providerItems as $providerId => $provider) {
            if ($provider->isEmptyData()) {
                unset($providerItems[$providerId]);
            }
        }

        return $providerItems;
    }

    // merge hack for KLM (Flying Blue) + Air France (Flying Blue)
    private function flyingBlueMerge(array $rows, bool $afterMerge): array
    {
        $klmId = Provider::KLM_ID;
        $airFranceId = Provider::AIRFRANCE_ID;

        if ($afterMerge
            && (array_key_exists('airlines', $rows)
                && array_key_exists($airFranceId, $rows['airlines']['data'])
                && array_key_exists($klmId, $rows['airlines']['data'])
            )
        ) {
            $autoValues = $rows['airlines']['data'][$airFranceId]->getAutoValues();

            if (!empty((array) $autoValues)) {
                foreach ($autoValues as $key => $value) {
                    if (!empty((array) $rows['airlines']['data'][$klmId]->getAutoValues())) {
                        throw new \Exception('Incorrect data joining');
                    }

                    // $rows['airlines']['data'][$klmId]['auto'][$key] = $value;
                    throw new \Exception('Old FlyingBlue merge');
                }
            }
            unset($rows['airlines']['data'][$airFranceId]);
        } else {
            $isKlmFound = $isAirFranceFound = false;

            foreach ($rows as $keyData => &$items) {
                if (array_key_exists($klmId, $items)) {
                    $isKlmFound = true;
                }

                if (array_key_exists($airFranceId, $items)) {
                    $isAirFranceFound = true;
                }
            }

            if ($isKlmFound && $isAirFranceFound) {
                foreach ($rows as $keyData => &$items) {
                    if (!array_key_exists($klmId, $items)) {
                        $items[$klmId] = [
                            'ProviderID' => $klmId,
                            'Code' => 'klm',
                            'DisplayName' => 'KLM / Air France (Flying Blue)',
                            'CertificationDate' => '',
                            '_count' => 0,
                            'sumSpent' => 0,
                            'sumTaxesSpent' => 0,
                            'sumAlternativeCost' => 0,
                        ];
                    } else {
                        $items[$klmId]['DisplayName'] = 'KLM / Air France (Flying Blue)';
                    }

                    if (array_key_exists($airFranceId, $items)) {
                        foreach (['_count', 'sumSpent', 'sumTaxesSpent', 'sumAlternativeCost'] as $field) {
                            $items[$klmId][$field] += $items[$airFranceId][$field];
                        }

                        unset($items[$airFranceId]);
                    }
                }
            }
        }

        return $rows;
    }

    private function getTransfersProviderId(): array
    {
        return $this->mileValueCache->get('mv_transfer_providers', function () {
            return $this->connection->executeQuery('
                    SELECT
                            DISTINCT ts.SourceProviderID
                    FROM TransferStat ts
                    WHERE
                            ts.SourceRate IS NOT NULL 
                        AND ts.TargetRate IS NOT NULL
                ')->fetchFirstColumn();
        });
    }

    private function getCobrandProviderMileValue(int $providerId, int $creditCardId, ?Context $context): ?float
    {
        // Airfrance and KLM have same Flying Blue currency, we will calc them under KLN, see flyingBlueMerge method
        if ($providerId === Provider::AIRFRANCE_ID) {
            $providerId = Provider::KLM_ID;
        }

        if ($context) {
            $mileValueItem = $context->getMileValueCacheMapByProvider()[$providerId] ?? null;
        } else {
            $mileValueItem = $this->getProviderItem($providerId);
        }

        if (!$mileValueItem) {
            $this->logger->warning("missing mile value for cobrand provider, no mileValue", ["CreditCardID" => $creditCardId, "ProviderID" => $providerId]);

            return null;
        }

        if (isset($mileValueItem['user'])) {
            return $mileValueItem['user']['AvgPointValue'];
        }

        if (!empty($mileValueItem['manual']) && array_key_exists(self::PRIMARY_CALC_FIELD, $mileValueItem['manual'])) {
            return $mileValueItem['manual']['AvgPointValue'];
        }

        if (!empty($mileValueItem['show'])) {
            return $mileValueItem['show']['AvgPointValue'];
        }

        $this->logger->warning("missing mile value for cobrand provider, no user/manual/show", ["CreditCardID" => $creditCardId, "ProviderID" => $providerId]);

        return null;
    }

    private function extractRangeValuesForTransfers(array $data): array
    {
        $transferKey = ProviderHandler::KIND_KEYS_EXTEND[ProviderHandler::PROVIDER_KIND_TRANSFERS];
        $airlineKey = ProviderHandler::KIND_KEYS[PROVIDER_KIND_AIRLINE];

        if (empty($data[$transferKey]['data']) || empty($data[$airlineKey]['data'])) {
            return $data;
        }

        $sourceProvidersId = array_keys($data[$transferKey]['data']);
        $transferPartners = $this->connection->executeQuery('
                SELECT
                        ts.SourceProviderID, ts.TargetProviderID
                FROM TransferStat ts
                JOIN Provider p ON (p.ProviderID = ts.TargetProviderID)
                WHERE
                        ts.SourceRate IS NOT NULL 
                    AND ts.TargetRate IS NOT NULL
                    AND ts.SourceProviderID IN (:providersId)
                    AND p.Kind = :kind',
            [
                'providersId' => $sourceProvidersId,
                'kind' => PROVIDER_KIND_AIRLINE,
            ],
            [
                'providersId' => Connection::PARAM_INT_ARRAY,
                'kind' => \PDO::PARAM_INT,
            ]
        )->fetchAllAssociative();

        $transferPartnersByProvider = [];

        foreach ($transferPartners as $destProvider) {
            $providerId = $destProvider['SourceProviderID'];
            $transferPartnersByProvider[$providerId][] = $destProvider['TargetProviderID'];
        }

        /** @var ProviderMileValueItem $bankMileValueItem */
        foreach ($data[$transferKey]['data'] as $bankMileValueItem) {
            $transferId = $bankMileValueItem->getProviderId();
            $minValue = $maxValue = [];

            /** @var ProviderMileValueItem $airMileValueItem */
            foreach ($data[$airlineKey]['data'] as $airMileValueItem) {
                if (!in_array($airMileValueItem->getProviderId(), $transferPartnersByProvider[$transferId])) {
                    continue;
                }

                foreach (ProviderMileValueItem::LIST_KEY_VALUES as $valueKey) {
                    $value = $airMileValueItem->isUserPrimary($valueKey)
                        ? $airMileValueItem->getSecondaryValue($valueKey)
                        : $airMileValueItem->getPrimaryValue($valueKey);

                    if (null === $value) {
                        continue;
                    }

                    if (!isset($minValue[$valueKey]) || $value < $minValue[$valueKey]) {
                        $minValue[$valueKey] = $value;
                    }

                    if (!isset($maxValue[$valueKey]) || $value > $maxValue[$valueKey]) {
                        $maxValue[$valueKey] = $value;
                    }
                }
            }

            foreach (ProviderMileValueItem::LIST_KEY_VALUES as $valueKey) {
                if (!isset($minValue[$valueKey])) {
                    continue;
                }
                $bankMileValueItem->setRangeValues($valueKey, $minValue[$valueKey], $maxValue[$valueKey]);
            }
        }

        return $data;
    }

    private function cleanProviderWithEmptyValues(array $result): array
    {
        foreach ($result as &$group) {
            /** @var ProviderMileValueItem $provider */
            foreach ($group['data'] as $provider) {
                if (null === $provider->getPrimaryValue(self::PRIMARY_CALC_FIELD) && null === $provider->getSecondaryValue(self::PRIMARY_CALC_FIELD)) {
                    $this->logger->info('MileValueService empty values for provider ' . $provider->getName());
                    unset($group['data'][$provider->getProviderId()]);
                }
            }
        }

        return $result;
    }
}

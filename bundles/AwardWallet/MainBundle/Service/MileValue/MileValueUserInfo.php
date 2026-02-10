<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Currency;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\Utils\LazyVal;
use AwardWallet\MainBundle\Service\AmericanAirlinesAAdvantageDetector;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MileValueUserInfo
{
    private EntityManagerInterface $entityManager;
    private MileValueService $mileValueService;
    private Connection $connection;
    private LocalizeService $localizeService;
    private TranslatorInterface $translator;
    private RouterInterface $router;
    private AwTokenStorage $tokenStorage;
    private AuthorizationCheckerInterface $authorizationChecker;
    private UserPointValueService $userPointValueService;
    private MileValueHandler $mileValueHandler;

    public function __construct(
        EntityManagerInterface $entityManager,
        MileValueService $mileValueService,
        LocalizeService $localizeService,
        TranslatorInterface $translator,
        RouterInterface $router,
        AwTokenStorage $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        UserPointValueService $userPointValueService,
        MileValueHandler $mileValueHandler
    ) {
        $this->entityManager = $entityManager;
        $this->mileValueService = $mileValueService;
        $this->localizeService = $localizeService;
        $this->translator = $translator;
        $this->router = $router;
        $this->connection = $entityManager->getConnection();
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->userPointValueService = $userPointValueService;
        $this->mileValueHandler = $mileValueHandler;
    }

    public function fetchAccountInfo($account, $mileValueData = null): ?array
    {
        if ($account instanceof Account) {
            $account = [
                'AccountID' => $account->getId(),
                'UserID' => $account->getUser()->getId(),
                'Login2' => $account->getLogin2(),
                'Balance' => $account->getBalance(),
                'LastBalance' => $account->getLastbalance(),
                'LastChangeDate' => $account->getLastchangedate(),
                'ProviderID' => $account->isCustom() ? null : $account->getProviderid()->getId(),
                'DisplayName' => $account->isCustom() ? null : $account->getProviderid()->getDisplayname(),
                'Kind' => $account->isCustom() ? null : $account->getProviderid()->getKind(),
            ];
        }

        if ($mileValueData instanceof LazyVal) {
            $mileValueData = $mileValueData->getValue();
        } elseif (empty($mileValueData)) {
            $mileValueData = $this->mileValueService->getFlatDataListById();
        }

        $providerId = (int) $account['ProviderID'];
        $field = MileValueService::PRIMARY_CALC_FIELD;

        if (Provider::AIRFRANCE_ID === $providerId && array_key_exists(Provider::KLM_ID, $mileValueData)) {
            $providerId = Provider::KLM_ID;
        } elseif (0 === $providerId && !empty($account['DisplayName']) && AmericanAirlinesAAdvantageDetector::isMatchByName($account['DisplayName'])) {
            $providerId = Provider::AA_ID;
        }

        $isUserPrimaryValue = array_key_exists($providerId, $mileValueData) ? $mileValueData[$providerId]->isUserPrimary($field) : false;
        $accountId = (int) ($account['AccountID'] ?? $account['ID']);
        $costData = $this->fetchCostData($account);

        if (PROVIDER_KIND_CREDITCARD === (int) $account['Kind']) {
            if (isset($account['SubAccountsArray'])) {
                $allCashBackSubAccounts = array_unique(array_column($account['SubAccountsArray'], 'IsCashBackOnly'));
                $allCashBackSubAccountsType = array_unique(array_column($account['SubAccountsArray'], 'CashBackType'));
            }

            if (isset($account['MainProperties']['DetectedCards']['DetectedCards']) && count($account['MainProperties']['DetectedCards']['DetectedCards'])) {
                $allCashBackSubAccounts = array_unique(array_column($account['MainProperties']['DetectedCards']['DetectedCards'], 'IsCashBackOnly'));
            }

            if ((isset($allCashBackSubAccounts) && 1 === count($allCashBackSubAccounts) && true === $allCashBackSubAccounts[0])
                && (!isset($allCashBackSubAccountsType)
                    || (isset($allCashBackSubAccountsType) && 1 === count($allCashBackSubAccountsType) && CreditCard::CASHBACK_TYPE_USD === (int) $allCashBackSubAccountsType[0])
                )
            ) {
                $isUsdCurrencyProviderSimulate = true;
            }

            if (isset($account['Properties']['Currency']) && '$' === $account['Properties']['Currency']['Val']) {
                $isUsdCurrencyProviderSimulate = true;
            }
        }

        if (!$isUserPrimaryValue
            && (isset($isUsdCurrencyProviderSimulate) || !array_key_exists($providerId, $mileValueData))
        ) {
            $accountsUserSetValues = $this->userPointValueService->getAccountsUserSetValues($account['UserID']);

            if (!array_key_exists($accountId, $accountsUserSetValues)) {
                $isUsdCurrencyProvider = (isset($account['Currency']) && Currency::USD_ID === (int) $account['Currency'])
                    || (isset($account['ManualCurrency']) && 'USD' === $account['ManualCurrency']);

                if (!$isUsdCurrencyProvider && !isset($isUsdCurrencyProviderSimulate)) {
                    return null;
                }
            }

            if (isset($isUsdCurrencyProviderSimulate) || !empty($isUsdCurrencyProvider)) {
                $customUserSet = [
                    'DisplayName' => $account['DisplayName'],
                    'Kind' => $account['Kind'],
                ];
                $detectedCards = $account['MainProperties']['DetectedCards']['DetectedCards'] ?? [];

                if (
                    (isset($detectedCards[0]['CashBackType'])
                        && CreditCard::CASHBACK_TYPE_POINT === (int) $detectedCards[0]['CashBackType']
                        && 1 === count(array_unique(array_column($detectedCards, 'CashBackType')))
                    ) || (
                        Provider::CITI_ID === $providerId
                        && $costData['isOnlyOneDetectedCardWithId']
                        && CreditCard::CASHBACK_TYPE_POINT === (int) $costData['cashBackDetectedCard']['CashBackType']
                    )
                ) {
                    $cashbackCost = 1;
                }

                $mileValue = $this->mileValueHandler->formatter(
                    MileValueService::PRIMARY_CALC_FIELD,
                    [MileValueService::PRIMARY_CALC_FIELD => $cashbackCost ?? 100],
                    true
                );
                $mileValueItem = (new ProviderMileValueItem())
                    ->setName($customUserSet['DisplayName'])
                    ->setSimulateValues($mileValue);
            } else {
                $customUserSet = $accountsUserSetValues[$accountId];
                $mileValueItem = (new ProviderMileValueItem())
                    ->setName($customUserSet['DisplayName'])
                    ->setUserValues($customUserSet['user']);
            }

            $account['DisplayName'] = $customUserSet['DisplayName'];
            $account['Kind'] = $customUserSet['Kind'];
        }

        if (Provider::AMEX_ID === $providerId
            && !empty($account['Login2'])
            && !in_array($account['Login2'], Account::LOGIN2_USA_VALUES, true)
            && !isset($customUserSet)
        ) {
            $mileValueItem = $mileValueItem ?? $mileValueData[$providerId];

            if (!$mileValueItem->isUserPrimary($field)) {
                return null;
            }
        }

        /** @var ProviderMileValueItem $mileValueItem */
        $mileValueItem = $mileValueItem ?? $mileValueData[$providerId];
        $mileValueCost = $mileValueItem->getPrimaryValue($field);

        $awCostPrecision = ($mileValueCost / 100 >= 0.0099 ? 2 : 4);
        $awCost = round($mileValueCost, $awCostPrecision, PHP_ROUND_HALF_DOWN);
        $awCostHint = $awCost >= 100 // && empty($isUsdCurrencyProvider)
            ? $this->localizeService->formatCurrency($awCost / 100, 'USD')
            : $awCost . $this->translator->trans('us-cent-symbol');

        $commonTransParams = [
            '%name%' => $account['DisplayName'],
            '%currency%' => $account['ProviderCurrency'] ?? '',
        ];

        if ($mileValueItem->isUserPrimary($field)) {
            $awEstimateHint = $this->translator->trans('personally-set-average');
        } elseif ($mileValueItem->isManualValuesExists($field)) {
            $awEstimateHint = $this->translator->trans('manually_set_by_aw');
        } else {
            $awEstimateHint = $this->translator->trans('based-aw-users-redemptions', $commonTransParams);
        }

        $precision = isset($isUsdCurrencyProvider) && true === $isUsdCurrencyProvider ? 2 : 0;
        $cashEquivalent = $this->mileValueService->calculateCashEquivalent($mileValueCost ?? 0, abs($account['Balance'] ?? 0), $precision);
        $approximateHint = ($mileValueItem->isUserPrimary($field)
            || $mileValueItem->isManualValuesExists($field)
            || $mileValueItem->isSimulateExists($field)
            || !array_key_exists($field, $mileValueItem->getAutoValues()))
            ? ''
            : $this->translator->trans('based-avg-value-per-point', [
                '%value%' => $awCostHint,
                '%count%' => $this->localizeService->formatNumber($mileValueItem->getAutoValues()[$field]['count']),
            ]);

        if (!empty($account['LastChangeDate'])) {
            $balance = $account['RawBalance'] ?? $account['BalanceRaw'] ?? $account['Balance'];
            $lastBalance = $account['LastBalanceRaw'] ?? $account['LastBalance'];

            if (is_numeric($lastBalance)) {
                $changeRaw = $balance - $lastBalance;

                if (abs($changeRaw) > 0.001 && null !== $mileValueCost) {
                    $account['ChangedPositive'] = $changeRaw > 0;
                    $account['LastChangeRaw'] = $changeRaw;
                    $changeCashEquivalent = $this->mileValueService->calculateCashEquivalent(
                        $mileValueCost,
                        $changeRaw
                    );
                }
            }
        }

        $result = [
            'providerTitle' => $this->translator->trans('value-of', $commonTransParams),
            'approximate' => [
                'hint' => $approximateHint,
                'value' => $cashEquivalent['formatted'],
                'raw' => $cashEquivalent['raw'],
            ],
            'awEstimate' => [
                'hint' => $awEstimateHint,
                'value' => $awCostHint,
                'raw' => round($awCost / 100, $awCostPrecision),
                'link' => $this->router->generate('aw_points_miles_values_provider', [
                    'providerName' => urldecode($account['DisplayName']),
                ]),
            ],
        ];

        if (isset($account['LastChangeRaw'])) {
            $result['balanceChange'] = [
                'value' => ($account['ChangedPositive'] ? '+' : '') . ($changeCashEquivalent['formatted'] ?? 0),
                'raw' => $changeCashEquivalent['raw'] ?? 0,
                'changeRaw' => $account['LastChangeRaw'] ?? 0,
                'changedPositive' => $account['ChangedPositive'] ?? null,
            ];
        }

        if (isset($isUsdCurrencyProvider) && true === $isUsdCurrencyProvider) {
            $result['isSimulated'] = true;
        }

        /*
        $personalTripCost = $isAirline
            ? $this->fetchMileValueByUser($user, $provider)
            : $this->fetchHotelPointValueByUser($user, $provider);
        if (!empty($personalTripCost)) {
            $personalCost = $this->mileValueService->calcValues('cost', [$personalTripCost]);
            $personalCost = $this->mileValueService->formatter('cost', $personalCost[0]['auto']);
            $result['personalRedemptions'] = [
                'hint'  => $this->translator->trans('based-your-personal-redemption', $commonTransParams),
                'value' => $personalCost['cost_number'] . ' ' . $personalCost['cost_currency'],
            ];
        }
        */

        return $result;
    }

    private function fetchCostData($account): array
    {
        $listCardsId = $listCashBack = [];
        $subAccounts = $account['SubAccountsArray'] ?? [];
        $countSubAccounts = count($subAccounts);

        foreach ($subAccounts as $subAccount) {
            $listCardsId[] = $subAccount['CreditCardID'] ?? '';
            $listCashBack[] = $subAccount['IsCashBackOnly'] ?? false;
        }

        $listDetectedCardCashBack = [];
        $listDetectedCardsWithId = [];
        $detectedCards = $account['MainProperties']['DetectedCards']['DetectedCards'] ?? [];
        $countDetectedCards = count($detectedCards);

        foreach ($detectedCards as $detectedCard) {
            $listDetectedCardCashBack[] = $detectedCard['IsCashBackOnly'] ?? null;

            if (array_key_exists('CreditCardID', $detectedCard)) {
                $listDetectedCardsWithId[] = $detectedCard;
            }
        }

        $result = [
            'isCostOnlyAccount' => $countSubAccounts && 1 === count(array_unique($listCardsId)) && empty($listCardsId[0]),
            'isAllSubAccountsWithCashBack' => $countSubAccounts && 1 === count(array_unique($listCashBack)) && $listCashBack[0] > 0,
            'isAllDetectedCardsWithCashBack' => $countDetectedCards && 1 === count(array_unique($listDetectedCardCashBack)) && $listDetectedCardCashBack[0] > 0,
            'isOnlyOneDetectedCardWithId' => 1 === count(array_unique(array_column($listDetectedCardsWithId, 'CreditCardID'))),
        ];

        $result['cashBackDetectedCard'] = $result['isOnlyOneDetectedCardWithId']
            ? $listDetectedCardsWithId[0]
            : [];

        return $result;
    }

    private function fetchMileValueByUser(Usr $user, Provider $provider)
    {
        return $this->connection->fetchAssoc('
            SELECT
                    COUNT(*) AS _count,
                    mv.ProviderID, ROUND(SUM(mv.TotalMilesSpent), 4) AS sumSpent, ROUND(SUM(mv.TotalTaxesSpent), 4) AS sumTaxesSpent, ROUND(SUM(mv.AlternativeCost), 4) AS sumAlternativeCost
            FROM MileValue mv
            JOIN Trip t ON (t.TripID = mv.TripID AND t.UserID = ? AND t.ProviderID = ?)
            WHERE
                    mv.Status NOT IN (?)
            GROUP BY ProviderID',
            [$user->getUserid(), $provider->getProviderid(), CalcMileValueCommand::EXCLUDED_STATUSES],
            [\PDO::PARAM_INT, \PDO::PARAM_INT, $this->connection::PARAM_INT_ARRAY]
        );
    }

    private function fetchHotelPointValueByUser(Usr $user, Provider $provider)
    {
        return $this->connection->fetchAssoc('
            SELECT
                    COUNT(*) AS _count,
                    hpv.ProviderID, ROUND(SUM(hpv.TotalPointsSpent), 4) AS sumSpent, ROUND(SUM(hpv.TotalTaxesSpent), 4) AS sumTaxesSpent, ROUND(SUM(hpv.AlternativeCost), 4) AS sumAlternativeCost
            FROM HotelPointValue hpv
            JOIN Reservation r ON (r.ReservationID = hpv.ReservationID AND r.UserID = ? AND r.ProviderID = ?)
            WHERE
                    hpv.Status NOT IN (?)
            GROUP BY ProviderID',
            [$user->getUserid(), $provider->getProviderid(), CalcMileValueCommand::EXCLUDED_STATUSES],
            [\PDO::PARAM_INT, \PDO::PARAM_INT, $this->connection::PARAM_INT_ARRAY]
        );
    }
}

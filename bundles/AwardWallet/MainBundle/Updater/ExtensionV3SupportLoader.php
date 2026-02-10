<?php

namespace AwardWallet\MainBundle\Updater;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\Converter;
use AwardWallet\MainBundle\Loyalty\Resources\CheckExtensionSupportRequest;
use AwardWallet\MainBundle\Updater\Options\ClientPlatform;
use AwardWallet\MainBundle\Updater\Plugin\GetProviderFromStateTrait;
use AwardWallet\MainBundle\Updater\Plugin\MasterInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ExtensionV3SupportLoader
{
    use GetProviderFromStateTrait;
    // bump V-version suffix on loadV3SupportMap() logic changes
    private const V3_SUPPORT_MAP_KEY = 'ExtensionV3SupportMap_V2';
    private ApiCommunicator $loyaltyApi;
    private Converter $loyaltyRequestFactory;
    private AuthorizationCheckerInterface $authorizationChecker;
    private LoggerInterface $logger;

    public function __construct(
        ProviderRepository $providerRepository,
        ApiCommunicator $loyaltyApi,
        Converter $loyaltyRequestFactory,
        AuthorizationCheckerInterface $authorizationChecker,
        LoggerInterface $logger
    ) {
        $this->providerRepository = $providerRepository;
        $this->loyaltyApi = $loyaltyApi;
        $this->loyaltyRequestFactory = $loyaltyRequestFactory;
        $this->authorizationChecker = $authorizationChecker;
        $this->logger = $logger;
    }

    /**
     * @param list<AccountState> $accountStates
     * @return array<string, bool>
     */
    public function loadV3SupportMap(MasterInterface $master, array $accountStates): array
    {
        $existingSupportMap = $this->getV3SupportMap($master);
        /** @var array<int, AccountState> $statesWithoutV3StatusMap */
        $statesWithoutV3StatusMap = [];
        /** @var array<string, bool> $extraV3SupportMap */
        $extraV3SupportMap = [];
        $includeReadyProviders = false; // add a "V3_READY" group for testing ?

        foreach ($accountStates as $state) {
            $provider = $this->getProviderFromState($state);

            if (!$provider) {
                $this->logger->info("ExtensionV3SupportLoader: provider not found for account {$state->account->getId()}");

                continue;
            }

            $existingSupportMapKey = self::makeV3SupportMapKey($state->account, $provider);

            if (!\array_key_exists($existingSupportMapKey, $existingSupportMap)) {
                if ($this->authorizationChecker->isGranted('CAN_CHECK_BY_BROWSEREXT_V3', $provider)) {
                    $statesWithoutV3StatusMap[$state->account->getId()] = $state;
                } else {
                    $this->logger->info("ExtensionV3SupportLoader: v3 parser not enabled for account {$state->account->getId()}, includeReadyProviders: " . json_encode($includeReadyProviders) . ", v3 ready: " . json_encode($provider->isExtensionV3ParserReady()));
                    $extraV3SupportMap[$existingSupportMapKey] = false;
                }
            }
        }

        if ($statesWithoutV3StatusMap) {
            $request = $this->loyaltyRequestFactory->prepareExtensionCheckSupportPackageRequest(
                it($statesWithoutV3StatusMap)
                    ->map(function (AccountState $state): Account {
                        $account = $state->account;
                        $provider = $account->getProviderid();
                        $providerFromState = $this->getProviderFromState($state);

                        if ($providerFromState->getId() !== $provider->getId()) {
                            $account = clone $account;
                            $account->setProviderid($providerFromState);
                        }

                        return $account;
                    })
                    ->toArray(),
                $master->getOption(Option::CLIENT_PLATFORM) === ClientPlatform::MOBILE,
                $includeReadyProviders
            );
            $accounts = it($request->getPackage())->map(fn (CheckExtensionSupportRequest $request) => $request->getId())->joinToString(", ");
            $this->logger->info("ExtensionV3SupportLoader: requesting v3 support for accounts {$accounts}");
            $package = $this->loyaltyApi->CheckExtensionSupport($request)->getPackage();

            foreach ($package as $accountId => $support) {
                $state = $statesWithoutV3StatusMap[$accountId];
                $provider = $this->getProviderFromState($state);
                $this->logger->info("ExtensionV3SupportLoader: got v3 support for account {$state->account->getId()}: " . json_encode($support));
                $extraV3SupportMap[self::makeV3SupportMapKey($state->account, $provider)] = $support;
            }
        }

        if ($extraV3SupportMap) {
            return $this->updateV3Support($master, $extraV3SupportMap);
        }

        return $existingSupportMap;
    }

    public static function makeV3SupportMapKey(Account $account, Provider $provider): string
    {
        return $account->getId() . '_' . $provider->getCode();
    }

    /**
     * @param array<string, bool> $map
     * @return array<string, bool> updated map
     */
    private function updateV3Support(MasterInterface $master, array $map): array
    {
        $extra = $master->getOption(Option::EXTRA);
        $v3SupportMap = $v3SupportMap[self::V3_SUPPORT_MAP_KEY] ?? [];
        $newMap = \array_merge($v3SupportMap, $map);
        $master->setOption(
            Option::EXTRA,
            \array_merge(
                $extra,
                [self::V3_SUPPORT_MAP_KEY => $newMap]
            )
        );
        $this->logger->info("ExtensionV3SupportLoader: updated v3 support map: " . json_encode($newMap));

        return $newMap;
    }

    /**
     * @return array<string, bool>
     */
    private function getV3SupportMap(MasterInterface $master): array
    {
        return $master->getOption(Option::EXTRA)[self::V3_SUPPORT_MAP_KEY] ?? [];
    }
}

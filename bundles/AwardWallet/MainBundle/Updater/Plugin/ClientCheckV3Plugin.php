<?php

namespace AwardWallet\MainBundle\Updater\Plugin;

use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Security\Voter\SiteVoter;
use AwardWallet\MainBundle\Updater\AccountState;
use AwardWallet\MainBundle\Updater\ClientCheckSlotsCalculator;
use AwardWallet\MainBundle\Updater\Event\ExtensionRequiredEvent;
use AwardWallet\MainBundle\Updater\Event\FailEvent;
use AwardWallet\MainBundle\Updater\ExtensionV3IsolatedCheckWaitMapOps;
use AwardWallet\MainBundle\Updater\ExtensionV3LocalPasswordWaitMapOps;
use AwardWallet\MainBundle\Updater\ExtensionV3SupportLoader;
use AwardWallet\MainBundle\Updater\Option;
use Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use function AwardWallet\MainBundle\Globals\Utils\lazy;

class ClientCheckV3Plugin extends AbstractPlugin
{
    use PluginIdentity;
    use GetProviderFromStateTrait;
    use NeedV3IsolatedCheckTrait;

    public const ID = 'clientCheckV3';

    public const EXTENSION_TEST_REMOVE = -1;
    public const EXTENSION_TEST_SKIP = 0;
    public const EXTENSION_TEST_CHECK = 1;

    protected LoggerInterface $logger;
    protected AuthorizationCheckerInterface $authorizationChecker;
    private SiteVoter $siteVoter;
    private ClientCheckSlotsCalculator $clientCheckSlotsCalculator;
    private ClockInterface $clock;
    private ExtensionV3SupportLoader $extensionV3SupportLoader;
    private ExtensionV3LocalPasswordWaitMapOps $extensionV3LocalPasswordWaitMapOps;
    private ExtensionV3IsolatedCheckWaitMapOps $extensionV3IsolatedCheckWaitMapOps;
    private RouterInterface $router;

    public function __construct(
        ProviderRepository $providerRep,
        LoggerInterface $logger,
        AuthorizationCheckerInterface $authorizationChecker,
        SiteVoter $siteVoter,
        ClientCheckSlotsCalculator $clientCheckSlotsCalculator,
        ClockInterface $clock,
        ExtensionV3SupportLoader $extensionV3SupportLoader,
        ExtensionV3LocalPasswordWaitMapOps $extensionV3LocalPasswordWaitMapOps,
        ExtensionV3IsolatedCheckWaitMapOps $extensionV3IsolatedCheckWaitMapOps,
        RouterInterface $router
    ) {
        $this->providerRepository = $providerRep;
        $this->logger = $logger;
        $this->authorizationChecker = $authorizationChecker;
        $this->siteVoter = $siteVoter;
        $this->clientCheckSlotsCalculator = $clientCheckSlotsCalculator;
        $this->clock = $clock;
        $this->extensionV3SupportLoader = $extensionV3SupportLoader;
        $this->extensionV3LocalPasswordWaitMapOps = $extensionV3LocalPasswordWaitMapOps;
        $this->extensionV3IsolatedCheckWaitMapOps = $extensionV3IsolatedCheckWaitMapOps;
        $this->router = $router;
    }

    /**
     * @param AccountState[] $accountStates
     */
    public function tick(MasterInterface $master, $accountStates): void
    {
        $needV3IsolatedCheck = $this->needV3IsolatedCheck($master);
        $holdV3AccountsUntilAllLocalPasswordsAnswered = (
            $needV3IsolatedCheck
            && $this->extensionV3LocalPasswordWaitMapOps->hasActive($master)
        );
        $extensionV3SupportMap = lazy(fn (): array => $this->extensionV3SupportLoader->loadV3SupportMap($master, $accountStates));

        foreach ($accountStates as $state) {
            $provider = $this->getProviderFromState($state);

            if (empty($provider)) {
                $state->popPlugin();
                $master->log($state->account, 'Unknown provider');

                continue;
            }

            $v3SupportKey = ExtensionV3SupportLoader::makeV3SupportMapKey($state->account, $provider);

            if (!($extensionV3SupportMap[$v3SupportKey] ?? false)) {
                $state->popPlugin();
                $this->logger->info("Skipping account {$state->account->getAccountid()} because it does not support V3");

                continue;
            }
            $this->logger->info("account {$state->account->getAccountid()} supports V3");

            $clientCapsCheckResult = $this->checkClientCapability($master, $state, $provider->getState() == PROVIDER_CHECKING_EXTENSION_ONLY);

            if (self::EXTENSION_TEST_REMOVE == $clientCapsCheckResult) {
                $master->removeAccount($state->account);

                if ($this->siteVoter->isImpersonationSandboxEscaped()) {
                    $this->logger->info("Impersonated account {$state->account->getAccountid()} debug 1");
                    $master->addEvent($this->createImpersonatedFailEvent($state->account->getAccountid()));
                }

                continue;
            } elseif (self::EXTENSION_TEST_SKIP == $clientCapsCheckResult) {
                if ($this->siteVoter->isImpersonationSandboxEscaped()) {
                    $this->logger->info("Impersonated account {$state->account->getAccountid()} debug 2");
                    $master->addEvent($this->createImpersonatedFailEvent($state->account->getAccountid()));
                    $master->removeAccount($state->account);
                } else {
                    $state->popPlugin();
                }

                continue;
            }

            // THIS CODE BLOCK SHOULD BE FINAL IN LOOP
            if (self::EXTENSION_TEST_CHECK === $clientCapsCheckResult) {
                if ($holdV3AccountsUntilAllLocalPasswordsAnswered) {
                    continue;
                }

                // jump to ServerCheckPlugin
                $state->popPlugin();
            }
        }
    }

    protected function checkClientCapability(MasterInterface $master, AccountState $state): int
    {
        if (!$master->getOption(Option::EXTENSION_V3_INSTALLED, false)) {
            $parserEnabled = $this->authorizationChecker->isGranted('CAN_CHECK_BY_BROWSEREXT_V3', $state->account->getProviderid());

            $this->logger->info("v3 extension is not installed. parserEnabled: " . json_encode($parserEnabled), ["AccountID" => $state->account->getId()]);

            if ($master->getOption(Option::EXTENSION_V3_SUPPORTED, false)) {
                if (
                    $parserEnabled
                    && $master->getOption(Option::EXTENSION_INSTALLED, false)
                ) {
                    $master->addEvent(new ExtensionRequiredEvent($state->account->getAccountid(), 3, $this->router->generate('aw_extension_install', ['v3' => 'true']), 'button.upgrade'));
                    $master->addEvent(new FailEvent($state->account->getAccountid(), 'updater2.messages.fail.extension_v3_upgrade_required'));

                    return self::EXTENSION_TEST_REMOVE;
                }

                if ($parserEnabled) {
                    $master->addEvent(new ExtensionRequiredEvent($state->account->getAccountid(), 3, $this->router->generate('aw_extension_install', ['v3' => 'true']), 'install'));
                    $master->addEvent(new FailEvent($state->account->getAccountid(), 'updater2.messages.fail.extension'));

                    return self::EXTENSION_TEST_REMOVE;
                }
            }

            return self::EXTENSION_TEST_SKIP;
        }

        if (
            $master->getOption(Option::EXTENSION_DISABLED)
            || !$this->authorizationChecker->isGranted('UPDATE_CLIENT_V3', $state->account)
        ) {
            $this->logger->info("v3 extension disabled. Option::EXTENSION_DISABLED: " . json_encode($master->getOption(Option::EXTENSION_DISABLED)), ["AccountID" => $state->account->getId()]);

            return self::EXTENSION_TEST_SKIP;
        }

        return self::EXTENSION_TEST_CHECK;
    }

    private function createImpersonatedFailEvent(int $accountId): FailEvent
    {
        return new FailEvent($accountId, 'You are impersonated and do not have access to password of this account. AccountID: ' . $accountId);
    }
}

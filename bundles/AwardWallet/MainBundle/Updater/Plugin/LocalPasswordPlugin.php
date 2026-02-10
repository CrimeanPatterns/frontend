<?php

namespace AwardWallet\MainBundle\Updater\Plugin;

use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use AwardWallet\MainBundle\Updater\AccountState;
use AwardWallet\MainBundle\Updater\Event\LocalPasswordEvent;
use AwardWallet\MainBundle\Updater\ExtensionV3LocalPasswordWaitMapOps;
use AwardWallet\MainBundle\Updater\ExtensionV3SupportLoader;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use function AwardWallet\MainBundle\Globals\Utils\lazy;

/**
 * @psalm-type V3QueueWaitMap = array<int, bool>
 */
class LocalPasswordPlugin extends AbstractPlugin
{
    use PluginIdentity;
    use GetProviderFromStateTrait;
    use NeedV3IsolatedCheckTrait;

    public const ID = 'localPassword';
    private LocalPasswordsManager $localPasswordsManager;
    private ExtensionV3SupportLoader $extensionV3SupportLoader;
    private ExtensionV3LocalPasswordWaitMapOps $extensionV3LocalPasswordWaitMapOps;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        LocalPasswordsManager $localPasswordsManager,
        ProviderRepository $providerRepository,
        ExtensionV3SupportLoader $extensionV3SupportLoader,
        ExtensionV3LocalPasswordWaitMapOps $extensionV3LocalPasswordWaitMapOps,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->providerRepository = $providerRepository;
        $this->localPasswordsManager = $localPasswordsManager;
        $this->extensionV3SupportLoader = $extensionV3SupportLoader;
        $this->extensionV3LocalPasswordWaitMapOps = $extensionV3LocalPasswordWaitMapOps;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param AccountState[] $accountStates
     */
    public function tick(MasterInterface $master, $accountStates): void
    {
        $needV3IsolatedCheck = $this->needV3IsolatedCheck($master);
        /** @var LocalPasswordEvent[] $localPasswordEvents */
        $localPasswordEvents = [];
        $v3SupportMap = lazy(fn () => $this->extensionV3SupportLoader->loadV3SupportMap($master, $accountStates));

        foreach ($accountStates as $state) {
            if ($this->needToAskLocalPassword($state)) {
                $event = self::makeEventFromAccountState($state);

                if ($needV3IsolatedCheck) {
                    $v3SupportKey = ExtensionV3SupportLoader::makeV3SupportMapKey(
                        $state->account,
                        $this->getProviderFromState($state)
                    );

                    $provider = $this->getProviderFromState($state);

                    if ($provider && $this->authorizationChecker->isGranted('CAN_CHECK_BY_BROWSEREXT_V3', $provider) && ($v3SupportMap[$v3SupportKey] ?? false)) {
                        $this->extensionV3LocalPasswordWaitMapOps->addAccount($master, $state->account->getId());
                    }
                }

                $localPasswordEvents[] = $event;
                $master->removeAccount($state->account);
            }

            $state->popPlugin();
        }

        foreach ($localPasswordEvents as $event) {
            $master->addEvent($event);
        }
    }

    private static function makeEventFromAccountState(AccountState $state): LocalPasswordEvent
    {
        return new LocalPasswordEvent(
            $state->account->getAccountid(),
            $state->account->getProviderid()->getDisplayname(),
            $state->account->getLogin(),
            $state->account->getUser()->getFullName()
        );
    }

    private function needToAskLocalPassword(AccountState $state): bool
    {
        return SAVE_PASSWORD_LOCALLY == $state->account->getSavepassword()
            && $state->account->getProviderid()->getPasswordrequired()
            && !$this->localPasswordsManager->hasPassword($state->account->getAccountid())
            && !$state->account->isAaPasswordValid() // Successfully checked AA accounts does not require password stored locally.
            && !$state->account->isOauthTokenValid();
    }
}

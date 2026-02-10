<?php

namespace AwardWallet\MainBundle\Updater\Plugin;

use AwardWallet\MainBundle\Updater\AccountState;
use AwardWallet\MainBundle\Updater\Event\DisabledEvent;
use AwardWallet\MainBundle\Updater\Event\FailEvent;
use AwardWallet\MainBundle\Updater\Option;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AccessPlugin extends AbstractPlugin
{
    use PluginIdentity;

    public const ID = 'access';

    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param AccountState[] $accountStates
     */
    public function tick(MasterInterface $master, $accountStates): void
    {
        foreach ($accountStates as $state) {
            if ($state->account->isDisabled()) {
                $master->addEvent(new DisabledEvent($state->account->getAccountid()));
                $master->removeAccount($state->account);
            } else {
                if ($master->getOption(Option::SOURCE) == 'group') {
                    $canUpdate = $this->authorizationChecker->isGranted('UPDATE_GROUP', $state->account);
                } else {
                    $canUpdate = $this->authorizationChecker->isGranted('UPDATE', $state->account);
                }

                if ($master->getOption(Option::SOURCE) == 'group' || $master->getOption(Option::SOURCE) == 'trips') {
                    $canUpdate = $canUpdate || ($state->checkIts && $this->authorizationChecker->isGranted('UPDATE_ITINERARY', $state->account));
                }
                $providerExtensionOnly =
                    ($provider = $state->account->getProviderid())
                    && ($provider->getState() === PROVIDER_CHECKING_EXTENSION_ONLY);

                if (!$canUpdate && !$providerExtensionOnly) {
                    // current user cannot access to account
                    $master->addEvent(new FailEvent($state->account->getAccountid(), 'updater2.messages.fail.access'));
                    $master->removeAccount($state->account);
                }
            }

            $state->popPlugin();
        }
    }

    // HACK! REMOVE ME!
    public function getChecker(): AuthorizationCheckerInterface
    {
        return $this->authorizationChecker;
    }
}

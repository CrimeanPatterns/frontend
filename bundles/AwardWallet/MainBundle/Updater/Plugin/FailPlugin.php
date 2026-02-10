<?php

namespace AwardWallet\MainBundle\Updater\Plugin;

use AwardWallet\MainBundle\Updater\AccountState;
use AwardWallet\MainBundle\Updater\Event\FailEvent;

/**
 * Class FailPlugin
 * final plugin.
 */
class FailPlugin extends AbstractPlugin
{
    use PluginIdentity;

    public const ID = 'fail';

    public function __construct()
    {
    }

    /**
     * @param AccountState[] $accountStates
     */
    public function tick(MasterInterface $master, $accountStates): void
    {
        foreach ($accountStates as $state) {
            $master->addEvent(new FailEvent($state->account->getAccountid(), 'updater2.messages.fail.updater'));
            $master->removeAccount($state->account);
        }
    }
}

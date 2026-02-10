<?php

namespace AwardWallet\MainBundle\Updater\Plugin;

use AwardWallet\MainBundle\Updater\AccountState;
use AwardWallet\MainBundle\Updater\Option;

class CheckItinerariesPlugin extends AbstractPlugin
{
    use PluginIdentity;

    public const ID = 'check_its';

    /**
     * @param AccountState[] $accountStates
     */
    public function tick(MasterInterface $master, $accountStates): void
    {
        foreach ($accountStates as $state) {
            $account = $state->account;
            $provider = $account->getProviderid();

            $autoCheck = $account->wantAutoCheckItineraries();
            $forcedCheck =
                !empty($provider)
                && $provider->getCancheckitinerary()
                && $master->getOption(Option::CHECK_TRIPS);

            if ($autoCheck || $forcedCheck) {
                $master->log($state->account, 'enabled check itineraries');
                $state->checkIts = true;
            }
            $state->popPlugin();
        }
    }
}

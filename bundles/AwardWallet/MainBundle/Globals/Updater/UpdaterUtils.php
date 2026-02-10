<?php

namespace AwardWallet\MainBundle\Globals\Updater;

use AwardWallet\MainBundle\Entity\Account;

class UpdaterUtils
{
    /**
     * @return bool
     */
    public static function shouldCheckTrips(array $account)
    {
        $providerVote = 1 == $account['CanCheckItinerary'];
        $userVote = 1 == $account['AutoGatherPlans'];
        $checkFrequencyVote = time() - strtotime($account['LastCheckItDate']) > SECONDS_PER_DAY;
        $forcedCheck = !empty($account['ParseItineraries']);

        return $providerVote && (($userVote && $checkFrequencyVote) || $forcedCheck);
    }

    public static function shouldCheckTripsByEntity(Account $account)
    {
        $provider = $account->getProviderid();

        if (!$provider) {
            return false;
        }

        $providerVote = 1 == $provider->getCancheckitinerary();
        $userVote = 1 == $account->getUserid()->getAutogatherplans();
        $checkFrequencyVote = time() - ($account->getLastcheckitdate() == null ? 0 : $account->getLastcheckitdate()->getTimestamp()) > SECONDS_PER_DAY;

        return $providerVote && $userVote && $checkFrequencyVote;
    }
}

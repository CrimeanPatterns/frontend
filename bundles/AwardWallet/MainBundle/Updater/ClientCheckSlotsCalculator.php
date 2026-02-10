<?php

namespace AwardWallet\MainBundle\Updater;

use Duration\Duration;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ClientCheckSlotsCalculator
{
    private const MAX_FREE_CLIENT_CHECK_SLOTS = 1;

    /**
     * @param list<AccountState> $accountStates
     * @param callable(AccountState): ?Duration $startTimeGetter
     */
    public function getFreeSlots(array $accountStates, callable $startTimeGetter): int
    {
        return
            self::MAX_FREE_CLIENT_CHECK_SLOTS
            -
            it($accountStates)
            ->filter(static fn (AccountState $state) => !empty($startTimeGetter($state)))
            ->count();
    }
}

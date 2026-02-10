<?php

namespace AwardWallet\MainBundle\Updater;

use AwardWallet\MainBundle\Updater\Plugin\MasterInterface;
use Clock\ClockInterface;
use Duration\Duration;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function Duration\minutes;

/**
 * @psalm-type V3WaitMap = array<int, Duration>
 */
class ExtensionV3LocalPasswordWaitMapOps
{
    private ClockInterface $clock;

    public function __construct(ClockInterface $clock)
    {
        $this->clock = $clock;
    }

    /**
     * @return V3WaitMap
     */
    public function addAccount(MasterInterface $master, int $accountId, ?Duration $timeout = null): array
    {
        /** @var V3WaitMap $map */
        $map = $master->getOption(InternalOptions::V3_LOCAL_PASSWORD_WAIT_MAP, []);
        $timeout = $this->clock->current()->add($timeout ?? minutes(10));
        $map[$accountId] = $timeout;
        $master->setOption(InternalOptions::V3_LOCAL_PASSWORD_WAIT_MAP, $map);

        return $map;
    }

    public function hasActive(MasterInterface $master): bool
    {
        /** @var V3WaitMap $map */
        $map = $master->getOption(InternalOptions::V3_LOCAL_PASSWORD_WAIT_MAP, []);

        if (!$map) {
            return false;
        }

        $current = $this->clock->current();

        return
            it($map)
            ->filter(static fn (?Duration $timeout) => $timeout && $current->lessThan($timeout))
            ->isNotEmpty();
    }

    /**
     * @param int[] $accountIds
     * @return V3WaitMap
     */
    public function removeAccounts(MasterInterface $master, array $accountIds): array
    {
        /** @var V3WaitMap $map */
        $map = $master->getOption(InternalOptions::V3_LOCAL_PASSWORD_WAIT_MAP, []);

        foreach ($accountIds as $accountId) {
            $map[$accountId] = null;
        }

        $master->setOption(InternalOptions::V3_LOCAL_PASSWORD_WAIT_MAP, $map);

        return $map;
    }
}

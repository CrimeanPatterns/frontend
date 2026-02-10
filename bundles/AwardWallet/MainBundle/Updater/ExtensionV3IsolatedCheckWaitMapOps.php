<?php

namespace AwardWallet\MainBundle\Updater;

use AwardWallet\MainBundle\Updater\Plugin\MasterInterface;

/**
 * @psalm-type V3WaitMap = array<int, bool>
 */
class ExtensionV3IsolatedCheckWaitMapOps
{
    public function addAccount(MasterInterface $master, int $accountId): array
    {
        /** @var V3WaitMap $map */
        $map = $master->getOption(InternalOptions::V3_ISOLATED_CHECK_WAIT_MAP, []);
        $map[$accountId] = true;
        $master->setOption(InternalOptions::V3_ISOLATED_CHECK_WAIT_MAP, $map);

        return $map;
    }

    public function hasActive(MasterInterface $master): bool
    {
        /** @var V3WaitMap $map */
        $map = $master->getOption(InternalOptions::V3_ISOLATED_CHECK_WAIT_MAP, []);

        if (!$map) {
            return false;
        }

        return \in_array(true, $map, true);
    }

    public function removeAccount(MasterInterface $master, int $accountId): array
    {
        /** @var V3WaitMap $map */
        $map = $master->getOption(InternalOptions::V3_ISOLATED_CHECK_WAIT_MAP, []);
        $map[$accountId] = false;
        $master->setOption(InternalOptions::V3_ISOLATED_CHECK_WAIT_MAP, $map);

        return $map;
    }
}

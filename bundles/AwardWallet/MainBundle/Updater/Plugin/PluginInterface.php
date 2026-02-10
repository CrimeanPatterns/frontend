<?php

namespace AwardWallet\MainBundle\Updater\Plugin;

use AwardWallet\MainBundle\Updater\AccountState;

interface PluginInterface
{
    /**
     * @param AccountState[] $accountStates
     */
    public function tick(MasterInterface $master, array $accountStates): void;

    /**
     * @param AccountState[] $accountStates
     */
    public function postTick(MasterInterface $master, array $accountStates): void;

    /**
     * @return string
     */
    public function getId();
}

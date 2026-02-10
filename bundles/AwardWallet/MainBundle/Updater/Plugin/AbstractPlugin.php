<?php

namespace AwardWallet\MainBundle\Updater\Plugin;

abstract class AbstractPlugin implements PluginInterface
{
    public function postTick(MasterInterface $master, array $accountStates): void
    {
    }
}

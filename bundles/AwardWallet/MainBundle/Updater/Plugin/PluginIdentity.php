<?php

namespace AwardWallet\MainBundle\Updater\Plugin;

trait PluginIdentity
{
    public function getId()
    {
        return static::ID;
    }
}

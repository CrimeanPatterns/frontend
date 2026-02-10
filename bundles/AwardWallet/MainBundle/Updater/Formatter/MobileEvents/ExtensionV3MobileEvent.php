<?php

namespace AwardWallet\MainBundle\Updater\Formatter\MobileEvents;

use AwardWallet\MainBundle\Updater\Event\ExtensionV3Event;

class ExtensionV3MobileEvent extends ExtensionV3Event
{
    public ?string $login;
    public ?string $displayName;
    public ?string $providerCode;
}

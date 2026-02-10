<?php

namespace AwardWallet\MainBundle\Updater\Plugin;

use AwardWallet\MainBundle\Updater\Option;
use AwardWallet\MainBundle\Updater\Options\ClientPlatform;

trait NeedV3IsolatedCheckTrait
{
    public function needV3IsolatedCheck(MasterInterface $master): bool
    {
        return
            ($master->getOption(Option::CLIENT_PLATFORM, null) === ClientPlatform::MOBILE)
            && $master->getOption(Option::EXTENSION_V3_SUPPORTED, false);
    }
}

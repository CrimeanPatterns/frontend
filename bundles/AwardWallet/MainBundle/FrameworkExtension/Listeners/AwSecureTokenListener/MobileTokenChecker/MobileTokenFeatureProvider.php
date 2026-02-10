<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners\AwSecureTokenListener\MobileTokenChecker;

use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;

class MobileTokenFeatureProvider
{
    /**
     * @return array [[feature => key]]
     */
    public function getKeys(): array
    {
        return [
            [MobileVersions::BOT_PROTECTION_SOFT_MODE, true],
            [MobileVersions::BOT_PROTECTION_KEY_V1, 'jJlFcyKNoQINxvamlT2Yqe4vjSx2Hbxw'],
            [MobileVersions::BOT_PROTECTION_KEY_ANDROID_V1, 'e3taJDLG6FQTnKJ7QQvLIbwfSa5Jm9vB'],
            [MobileVersions::BOT_PROTECTION_KEY_IOS_V1, '1PXeUYSw8g815vbWZ3bCsHeWCZ1YRXuu'],
        ];
    }
}

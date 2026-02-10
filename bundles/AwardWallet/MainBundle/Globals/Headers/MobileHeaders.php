<?php

namespace AwardWallet\MainBundle\Globals\Headers;

/**
 * IMPORTANT: CONST VALUES MUST NOT BE MODIFIED.
 */
interface MobileHeaders
{
    public const MOBILE_PLATFORM = 'x-aw-platform';
    public const MOBILE_VERSION = 'x-aw-version';
    public const MOBILE_NATIVE = 'x-aw-mobile-native';

    public const MOBILE_SECURE_TOKEN = 'x-aw-secure-token';
    public const MOBILE_SECURE_VALUE = 'x-aw-secure-value';

    public const MOBILE_DEVICE_ID = 'x-aw-device-id';
    public const MOBILE_DEVICE_UUID = 'x-aw-device-uuid';

    public const MOBILE_EXTERNAL_TRACKING = 'x-aw-external-tracking';
    public const MOBILE_EXTENSION_VERSION = 'x-aw-extension-version';
}

<?php

namespace AwardWallet\MainBundle\Updater;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class Option
{
    public const EXTRA = 'extra';
    public const BROWSER_SUPPORTED = 'browser_supported';
    public const EXTENSION_INSTALLED = 'extension_installed';
    public const EXTENSION_DISABLED = 'extension_disabled';
    public const EXTENSION_V3_INSTALLED = 'extension_v3_installed';
    public const EXTENSION_V3_SUPPORTED = 'extension_v3_supported';
    public const CHECK_PROVIDER_GROUP = 'check_provider_group';
    public const CHECK_TRIPS = 'check_trips';
    public const SOURCE = 'source';
    public const PLATFORM = 'platform'; // UpdaterEngineInterface::SOURCE_ constants
    public const DEBUG = 'debug';
    public const ASYNC = 'async';
    public const CLIENT_PLATFORM = 'client_platform';
}

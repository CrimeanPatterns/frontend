<?php

namespace AwardWallet\MainBundle\Updater;

interface InternalOptions
{
    public const V3_LOCAL_PASSWORD_WAIT_MAP = '__v3_local_password_wait_map';
    public const V3_ISOLATED_CHECK_WAIT_MAP = '__v3_isolated_check_wait_map';
    public const V3_ISOLATED_CHECK_SWITCHED_TO_BROWSER = '__v3_isolated_check_switched_to_browser';
}

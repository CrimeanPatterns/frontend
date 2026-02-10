<?php

namespace AwardWallet\MainBundle\Security\Reauthentication\Mobile;

abstract class MobileReauthHeaders
{
    public const SUCCESS = 'x-aw-reauth-success';
    public const CONTEXT = 'x-aw-reauth-context';
    public const REQUIRED = 'x-aw-reauth-required';
    public const ERROR = 'x-aw-reauth-error';
    public const INPUT = 'x-aw-reauth-input';
    public const INTENT = 'x-aw-reauth-intent';
    public const RETRY = 'x-aw-reauth-retry';
}

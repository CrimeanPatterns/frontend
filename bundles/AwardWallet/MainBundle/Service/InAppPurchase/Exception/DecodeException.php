<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase\Exception;

class DecodeException extends \RuntimeException
{
    public const CODE_MISSING_PUBLIC_KEY = 1;
    public const CODE_INVALID_PUBLIC_KEY = 2;
}

<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase\Exception;

class UnknownPlatformException extends \Exception
{
    public function __construct(?string $platform = null)
    {
        if (empty($platform)) {
            $message = "Unknown platform";
        } else {
            $message = sprintf("Unknown platform '%s'", $platform);
        }
        parent::__construct($message);
    }
}

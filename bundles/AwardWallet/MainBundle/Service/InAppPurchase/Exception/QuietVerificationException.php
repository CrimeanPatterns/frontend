<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase\Exception;

class QuietVerificationException extends VerificationException
{
    public function isTemporary(): bool
    {
        return false;
    }
}

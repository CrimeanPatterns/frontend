<?php

namespace AwardWallet\MainBundle\Service\Billing;

interface ExpirationCalculatorInterface
{
    public function calcExpirationDate($date, string $cartItemClass);
}

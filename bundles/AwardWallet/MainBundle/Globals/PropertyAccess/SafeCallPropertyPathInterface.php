<?php

namespace AwardWallet\MainBundle\Globals\PropertyAccess;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

interface SafeCallPropertyPathInterface extends PropertyPathInterface
{
    public function isSafeCall($index): bool;
}

<?php

namespace AwardWallet\MainBundle\Globals\PropertyAccess;

use Symfony\Component\PropertyAccess\PropertyPathIteratorInterface;

interface SafeCallPropertyPathIteratorInterface extends PropertyPathIteratorInterface
{
    /**
     * Returns whether the current element in the property path is an safe
     * call.
     *
     * @return bool
     */
    public function isSafeCall();
}

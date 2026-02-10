<?php

namespace AwardWallet\MobileBundle\Form\Type\Helpers\OrderBuilder\Matcher;

use AwardWallet\MainBundle\Globals\Singleton;

class UnmatchedAnchor implements MapMatcherInterface
{
    use Singleton;

    public function matchMap(array $map): array
    {
        throw new \LogicException('Should not be called');
    }
}

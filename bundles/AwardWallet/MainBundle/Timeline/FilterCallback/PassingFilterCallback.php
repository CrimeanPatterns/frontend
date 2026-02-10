<?php

namespace AwardWallet\MainBundle\Timeline\FilterCallback;

use AwardWallet\MainBundle\Globals\Singleton;

class PassingFilterCallback extends AbstractFilterCallback
{
    use Singleton;

    private function __construct()
    {
        return parent::__construct(
            static function () { return true; },
            'pass'
        );
    }

    public function and(FilterCallbackInterface $filterCallback): FilterCallbackInterface
    {
        return $filterCallback;
    }
}

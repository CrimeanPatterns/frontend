<?php

namespace AwardWallet\MainBundle\Timeline\FilterCallback;

class FilterCallback extends AbstractFilterCallback
{
    public static function make(callable $filter, string $cache): self
    {
        return new self($filter, $cache);
    }

    public static function pass(): PassingFilterCallback
    {
        return PassingFilterCallback::getInstance();
    }
}

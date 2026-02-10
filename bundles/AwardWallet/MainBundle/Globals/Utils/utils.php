<?php

namespace AwardWallet\MainBundle\Globals\Utils;

function none(): None
{
    return None::getInstance();
}

/**
 * @template T
 * @param callable():T $initializer
 * @return LazyVal<T>
 */
function lazy(callable $initializer): LazyVal
{
    return new LazyVal($initializer);
}

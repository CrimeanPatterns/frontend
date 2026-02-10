<?php

namespace AwardWallet\MainBundle\Service\Cache;

use AwardWallet\MainBundle\Globals\Utils\Random;
use AwardWallet\MainBundle\Globals\Utils\RandomInterface;
use Clock\ClockInterface;
use Clock\ClockNative;
use Duration\Duration;

class StampedeProtector
{
    private ClockInterface $clock;
    private RandomInterface $random;

    public function __construct(
        ?ClockInterface $clock = null,
        ?RandomInterface $random = null
    ) {
        $this->clock = $clock ?? new ClockNative();
        $this->random = $random ?? new Random();
    }

    /**
     * @param Duration $creation - time of creation of cache item
     * @param Duration $expiration - how long the cache should be valid since creation
     * @param Duration $delta - how long it takes to recompute the cache
     * @param float $beta - how much earlier the cache should be recomputed
     */
    public function canRecomputeEarlyByCreationAndExpiration(Duration $creation, Duration $expiration, Duration $delta, float $beta = 1.0): bool
    {
        return $this->canRecomputeEarlyByExpiry($creation->add($expiration), $delta, $beta);
    }

    /**
     * @param Duration $expiry - when the cache expires
     * @param Duration $delta - how long it takes to recompute the cache
     * @param float $beta - how much earlier the cache should be recomputed
     */
    public function canRecomputeEarlyByExpiry(Duration $expiry, Duration $delta, float $beta = 1.0): bool
    {
        // time() - $delta * $beta * log(float_rand(0, 1.0) >= $expiry
        $current = $this->clock->current();
        // \log() from values between 0 and 1 is non-positive,
        // thus subtracting $sub product from $current can make $probabilityExpiration greater than $expiry
        $factor = $beta * \log($this->random->generateInt(1, \PHP_INT_MAX) / \PHP_INT_MAX);
        $sub = $delta->times($factor);
        $probabilityExpiration = $current->sub($sub);

        return $probabilityExpiration->greaterThanOrEquals($expiry);
    }
}

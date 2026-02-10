<?php

namespace AwardWallet\MainBundle\Service\Lounge;

interface MatcherInterface
{
    /**
     * @return float similarity in range [0, 1]
     */
    public function getSimilarity(LoungeInterface $lounge1, LoungeInterface $lounge2): float;

    public static function getThreshold(): float;

    public static function getName(): string;
}

<?php

namespace AwardWallet\MainBundle\Manager\CardImage\Matcher;

interface MatcherInterface
{
    /**
     * @return array matches
     */
    public function match(string $pattern, string $subject): array;
}

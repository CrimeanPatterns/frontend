<?php

namespace AwardWallet\MainBundle\Manager\CardImage\Matcher;

class StandardMatcher implements MatcherInterface
{
    public function match(string $pattern, string $subject): array
    {
        return preg_match($pattern, $subject, $matches, PREG_OFFSET_CAPTURE) ? $matches : [];
    }
}

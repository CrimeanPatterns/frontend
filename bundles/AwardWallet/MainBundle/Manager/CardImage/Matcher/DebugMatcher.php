<?php

namespace AwardWallet\MainBundle\Manager\CardImage\Matcher;

class DebugMatcher implements MatcherInterface
{
    public function match(string $pattern, string $subject): array
    {
        return preg_match_all(
            $pattern,
            $subject,
            $matches,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER
        ) ?
            ($matches[0] ?? []) :
            [];
    }
}

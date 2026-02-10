<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

class PatternsParser
{
    public function parse(string $patterns): array
    {
        return array_filter(
            array_map(function ($pattern) {
                if (substr($pattern, 0, 1) === '#') {
                    return $pattern;
                }

                return trim(CreditCardMatcher::cleanSpecialSymbols($pattern));
            }, explode("\n", $patterns)),

            function ($pattern) {
                return !empty($pattern);
            }
        );
    }
}

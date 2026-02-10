<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

class SpentAwardsParser
{
    public function parse(string $spentAwards): ?SpentAwardsParserResult
    {
        $spentAwards = str_replace(',', '', $spentAwards);

        // $356.48 + 35,000 points
        if (preg_match('#^(?<currency>[^\d\s\-+.,]+)\s*(?<total>\d+(\.\d\d)?)\s+.*\+(\s+(?<points>\d+))?\s+points$#uims', $spentAwards, $matches)) {
            return new SpentAwardsParserResult($matches['total'], $matches['currency'], $matches['points'] ?? 0);
        }

        // 26.96 € + 28,000 points
        if (preg_match('#^(?<total>\d+(\.\d\d)?)\s+(?<currency>\S+)\s+\+(\s+(?<points>\d+))?\s+points$#uims', $spentAwards, $matches)) {
            return new SpentAwardsParserResult($matches['total'], $matches['currency'], $matches['points'] ?? 0);
        }

        return null;
    }
}

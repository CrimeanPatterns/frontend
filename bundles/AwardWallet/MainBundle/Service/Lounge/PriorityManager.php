<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Service\Lounge\Parser\AmexParser;
use AwardWallet\MainBundle\Service\Lounge\Parser\DeltaParser;
use AwardWallet\MainBundle\Service\Lounge\Parser\DragonPassParser;
use AwardWallet\MainBundle\Service\Lounge\Parser\LoungeBuddyParser;
use AwardWallet\MainBundle\Service\Lounge\Parser\LoungeKeyParser;
use AwardWallet\MainBundle\Service\Lounge\Parser\LoungeReviewParser;
use AwardWallet\MainBundle\Service\Lounge\Parser\PriorityPassParser;

class PriorityManager
{
    public function getParsersByPriority(): array
    {
        return [
            AmexParser::CODE,
            LoungeBuddyParser::CODE,
            LoungeReviewParser::CODE,
            DragonPassParser::CODE,
            LoungeKeyParser::CODE,
            PriorityPassParser::CODE,
            DeltaParser::CODE,
        ];
    }

    public function getPatches(): array
    {
        return [
            'OpeningHours' => [AmexParser::CODE, LoungeBuddyParser::CODE, LoungeReviewParser::CODE],
            'IsAvailable' => [AmexParser::CODE, LoungeBuddyParser::CODE, LoungeReviewParser::CODE],
            'PriorityPassAccess' => [PriorityPassParser::CODE, LoungeReviewParser::CODE],
            'AmexPlatinumAccess' => [AmexParser::CODE, LoungeBuddyParser::CODE, LoungeReviewParser::CODE],
            'DragonPassAccess' => [DragonPassParser::CODE, LoungeReviewParser::CODE],
            'LoungeKeyAccess' => [LoungeKeyParser::CODE, LoungeReviewParser::CODE],
            'IsRestaurant' => [LoungeReviewParser::CODE, PriorityPassParser::CODE],
        ];
    }
}

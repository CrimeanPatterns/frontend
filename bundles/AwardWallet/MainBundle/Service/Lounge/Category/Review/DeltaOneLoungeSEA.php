<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category\Review;

use AwardWallet\MainBundle\Service\Lounge\Category\CategoryInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\ReviewInterface;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

class DeltaOneLoungeSEA implements CategoryInterface, ReviewInterface
{
    public function match(LoungeInterface $lounge): bool
    {
        return strcasecmp($lounge->getAirportCode(), 'SEA') == 0
            && (
                preg_match('/\bDelta\s+One\s+Lounge\b/i', $lounge->getName())
                || preg_match('/\bDelta\s+Sky\s+Club\b/i', $lounge->getName())
            );
    }

    public static function getBlogPostId(): int
    {
        return 215233;
    }

    public static function getPriority(): int
    {
        return 10;
    }
}

<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category\Review;

use AwardWallet\MainBundle\Service\Lounge\Category\CategoryInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\ReviewInterface;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

class DeltaOneLoungeBOS implements CategoryInterface, ReviewInterface
{
    public function match(LoungeInterface $lounge): bool
    {
        return strcasecmp($lounge->getAirportCode(), 'BOS') == 0
            && preg_match('/\bDelta\s+One\s+Lounge\b/i', $lounge->getName());
    }

    public static function getBlogPostId(): int
    {
        return 190779;
    }

    public static function getPriority(): int
    {
        return 10;
    }
}

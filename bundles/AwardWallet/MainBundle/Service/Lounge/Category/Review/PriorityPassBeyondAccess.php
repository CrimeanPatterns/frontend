<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category\Review;

use AwardWallet\MainBundle\Service\Lounge\Category\CategoryInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\ReviewInterface;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

class PriorityPassBeyondAccess implements CategoryInterface, ReviewInterface
{
    public function match(LoungeInterface $lounge): bool
    {
        return ($lounge->isPriorityPassAccess() ?? false)
            && preg_match(
                '/\b((Minute\s+Suites)|(Sleep\s+\'N\s+Fly)|(YotelAir)|(Kepler\s*Club)|(Wait\s+N\'\s+Rest)|(Gameway)|(Relax\s+Spa))\b/i',
                $lounge->getName()
            );
    }

    public static function getBlogPostId(): int
    {
        return 17713;
    }

    public static function getPriority(): int
    {
        return 25;
    }
}

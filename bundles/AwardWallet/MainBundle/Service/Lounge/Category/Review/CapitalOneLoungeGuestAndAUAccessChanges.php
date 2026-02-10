<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category\Review;

use AwardWallet\MainBundle\Service\Lounge\Category\CategoryInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\ReviewInterface;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

class CapitalOneLoungeGuestAndAUAccessChanges implements CategoryInterface, ReviewInterface
{
    public function match(LoungeInterface $lounge): bool
    {
        return preg_match('/\bCapital\s+One\s+Lounge\b/i', $lounge->getName());
    }

    public static function getBlogPostId(): int
    {
        return 201311;
    }

    public static function getPriority(): int
    {
        return 50;
    }
}

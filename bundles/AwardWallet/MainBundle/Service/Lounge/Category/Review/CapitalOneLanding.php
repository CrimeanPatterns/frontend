<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category\Review;

use AwardWallet\MainBundle\Service\Lounge\Category\CategoryInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\ReviewInterface;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

class CapitalOneLanding implements CategoryInterface, ReviewInterface
{
    public function match(LoungeInterface $lounge): bool
    {
        return preg_match('/\bCapital\s+One\s+Landing\b/i', $lounge->getName());
    }

    public static function getBlogPostId(): int
    {
        return 87290;
    }

    public static function getPriority(): int
    {
        return 20;
    }
}

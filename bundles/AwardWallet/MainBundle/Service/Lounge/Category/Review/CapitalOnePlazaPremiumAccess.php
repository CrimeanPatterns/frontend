<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category\Review;

use AwardWallet\MainBundle\Service\Lounge\Category\CategoryInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\ReviewInterface;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

class CapitalOnePlazaPremiumAccess implements CategoryInterface, ReviewInterface
{
    public function match(LoungeInterface $lounge): bool
    {
        return preg_match('/\bPlaza\s+Premium\b/i', $lounge->getName());
    }

    public static function getBlogPostId(): int
    {
        return 80885;
    }

    public static function getPriority(): int
    {
        return 20;
    }
}

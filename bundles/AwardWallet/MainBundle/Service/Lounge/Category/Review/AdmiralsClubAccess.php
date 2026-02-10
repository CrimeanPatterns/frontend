<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category\Review;

use AwardWallet\MainBundle\Service\Lounge\Category\CategoryInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\ReviewInterface;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

class AdmiralsClubAccess implements CategoryInterface, ReviewInterface
{
    public function match(LoungeInterface $lounge): bool
    {
        return preg_match('/\bAdmirals\s+Club\b/i', $lounge->getName());
    }

    public static function getBlogPostId(): int
    {
        return 102034;
    }

    public static function getPriority(): int
    {
        return 30;
    }
}

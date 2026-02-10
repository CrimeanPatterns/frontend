<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category\Review;

use AwardWallet\MainBundle\Service\Lounge\Category\CategoryInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\ReviewInterface;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

class AdmiralsClubEWR implements CategoryInterface, ReviewInterface
{
    public function match(LoungeInterface $lounge): bool
    {
        return strcasecmp($lounge->getAirportCode(), 'EWR') === 0
            && preg_match('/\bAdmirals\s+Club\b/i', $lounge->getName());
    }

    public static function getBlogPostId(): int
    {
        return 123629;
    }

    public static function getPriority(): int
    {
        return 20;
    }
}

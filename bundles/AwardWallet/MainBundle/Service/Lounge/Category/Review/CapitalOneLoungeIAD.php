<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category\Review;

use AwardWallet\MainBundle\Service\Lounge\Category\CategoryInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\ReviewInterface;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

class CapitalOneLoungeIAD implements CategoryInterface, ReviewInterface
{
    public function match(LoungeInterface $lounge): bool
    {
        return strcasecmp($lounge->getAirportCode(), 'IAD') == 0
            && preg_match('/\bCapital\s+One\s+Lounge\b/i', $lounge->getName());
    }

    public static function getBlogPostId(): int
    {
        return 123822;
    }

    public static function getPriority(): int
    {
        return 10;
    }
}

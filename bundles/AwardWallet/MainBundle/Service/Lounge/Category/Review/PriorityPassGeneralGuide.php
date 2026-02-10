<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category\Review;

use AwardWallet\MainBundle\Service\Lounge\Category\CategoryInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\ReviewInterface;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

class PriorityPassGeneralGuide implements CategoryInterface, ReviewInterface
{
    public function match(LoungeInterface $lounge): bool
    {
        return ($lounge->isPriorityPassAccess() ?? false)
            && !($lounge->isRestaurant() ?? false);
    }

    public static function getBlogPostId(): int
    {
        return 66195;
    }

    public static function getPriority(): int
    {
        return 20;
    }
}

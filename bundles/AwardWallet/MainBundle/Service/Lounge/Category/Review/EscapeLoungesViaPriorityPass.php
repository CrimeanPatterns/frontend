<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category\Review;

use AwardWallet\MainBundle\Service\Lounge\Category\CategoryInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\ReviewInterface;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

class EscapeLoungesViaPriorityPass implements CategoryInterface, ReviewInterface
{
    public function match(LoungeInterface $lounge): bool
    {
        return preg_match('/\bEscape\s+Lounge\b/i', $lounge->getName())
            && ($lounge->isPriorityPassAccess() ?? false);
    }

    public static function getBlogPostId(): int
    {
        return 170274;
    }

    public static function getPriority(): int
    {
        return 20;
    }
}

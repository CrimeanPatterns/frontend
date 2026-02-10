<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category\Review;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Service\Lounge\Category\CategoryInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\ReviewInterface;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

class DeltaSkyClubAccess implements CategoryInterface, ReviewInterface
{
    public function match(LoungeInterface $lounge): bool
    {
        return $lounge->getAirlines()->exists(function ($k, Airline $airline) {
            return !is_null($airline->getFsCode()) && mb_strtolower($airline->getFsCode()) === 'dl';
        });
    }

    public static function getBlogPostId(): int
    {
        return 92124;
    }

    public static function getPriority(): int
    {
        return 30;
    }
}

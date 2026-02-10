<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category;

interface ReviewInterface
{
    public static function getBlogPostId(): int;

    /**
     * @return int lower priority means that the review will be shown first
     */
    public static function getPriority(): int;
}

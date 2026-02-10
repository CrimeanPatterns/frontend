<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category\AccessLevel;

use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Service\Lounge\Category\AccessLevelInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\CategoryInterface;
use AwardWallet\MainBundle\Service\Lounge\DTO\Icon;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

class DragonPass implements CategoryInterface, AccessLevelInterface
{
    public function match(LoungeInterface $lounge): bool
    {
        return $lounge->isDragonPassAccess() ?? false;
    }

    public static function getCategoryId(): int
    {
        return 6;
    }

    public static function getCardId(): ?string
    {
        return 'card_dp';
    }

    public static function getCardName(): ?string
    {
        return 'Dragon Pass';
    }

    public static function getCardIcon(ApiVersioningService $apiVersioningService): ?Icon
    {
        return new Icon('/assets/awardwalletnewdesign/img/card/dragonpass--light@1x.png', 150, 95);
    }

    public static function getCardIconByLounge(LoungeInterface $lounge, ApiVersioningService $apiVersioningService): ?Icon
    {
        return self::getCardIcon($apiVersioningService);
    }
}

<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category\AccessLevel;

use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Service\Lounge\Category\AccessLevelInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\CategoryInterface;
use AwardWallet\MainBundle\Service\Lounge\DTO\Icon;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

class PriorityPass implements CategoryInterface, AccessLevelInterface
{
    public function match(LoungeInterface $lounge): bool
    {
        return $lounge->isPriorityPassAccess() ?? false;
    }

    public static function getCategoryId(): int
    {
        return 1;
    }

    public static function getCardId(): ?string
    {
        return 'card_pp';
    }

    public static function getCardName(): ?string
    {
        return 'Priority Pass';
    }

    public static function getCardIcon(ApiVersioningService $apiVersioningService): ?Icon
    {
        return new Icon('/assets/awardwalletnewdesign/img/card/priority-pass--light@1x.png', 150, 95);
    }

    public static function getCardIconByLounge(LoungeInterface $lounge, ApiVersioningService $apiVersioningService): ?Icon
    {
        if ($apiVersioningService->notSupports(MobileVersions::LOUNGE_PRIORITY_PASS_RESTAURANT)) {
            return self::getCardIcon($apiVersioningService);
        }

        if ($lounge->isRestaurant()) {
            return new Icon('/assets/awardwalletnewdesign/img/card/restaurant-priority-pass--light@1x.png', 223, 95);
        }

        return self::getCardIcon($apiVersioningService);
    }
}

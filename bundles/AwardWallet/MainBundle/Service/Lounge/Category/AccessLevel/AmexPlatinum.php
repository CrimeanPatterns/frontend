<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category\AccessLevel;

use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Service\Lounge\Category\AccessLevelInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\CategoryInterface;
use AwardWallet\MainBundle\Service\Lounge\DTO\Icon;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

class AmexPlatinum implements CategoryInterface, AccessLevelInterface
{
    public function match(LoungeInterface $lounge): bool
    {
        return $lounge->isAmexPlatinumAccess() ?? false;
    }

    public static function getCategoryId(): int
    {
        return 2;
    }

    public static function getCardId(): ?string
    {
        return null;
    }

    public static function getCardName(): ?string
    {
        return null;
    }

    public static function getCardIcon(ApiVersioningService $apiVersioningService): ?Icon
    {
        return null;
    }

    public static function getCardIconByLounge(LoungeInterface $lounge, ApiVersioningService $apiVersioningService): ?Icon
    {
        return null;
    }
}

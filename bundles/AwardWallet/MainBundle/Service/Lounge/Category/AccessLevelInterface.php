<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category;

use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Service\Lounge\DTO\Icon;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

interface AccessLevelInterface
{
    public static function getCategoryId(): int;

    public static function getCardId(): ?string;

    public static function getCardName(): ?string;

    public static function getCardIcon(ApiVersioningService $apiVersioningService): ?Icon;

    public static function getCardIconByLounge(LoungeInterface $lounge, ApiVersioningService $apiVersioningService): ?Icon;
}

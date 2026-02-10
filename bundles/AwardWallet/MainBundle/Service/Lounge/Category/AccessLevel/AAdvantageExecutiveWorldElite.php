<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category\AccessLevel;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Service\Lounge\Category\AccessLevelInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\CategoryInterface;
use AwardWallet\MainBundle\Service\Lounge\DTO\Icon;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

class AAdvantageExecutiveWorldElite implements CategoryInterface, AccessLevelInterface
{
    public function match(LoungeInterface $lounge): bool
    {
        return $lounge->getAirlines()->exists(function ($k, Airline $airline) {
            return !is_null($airline->getFsCode()) && mb_strtolower($airline->getFsCode()) === 'aa';
        });
    }

    public static function getCategoryId(): int
    {
        return 3;
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

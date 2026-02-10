<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class LocationHelper
{
    public static function isSameLocationCode(?string $entityCode, ?string $schemaCode): bool
    {
        return !empty($entityCode) && !empty($schemaCode) && strcasecmp($entityCode, $schemaCode) === 0;
    }

    public static function isSameName(?string $entityName, ?string $schemaName): bool
    {
        return !empty($entityName) && !empty($schemaName) && strcasecmp($entityName, $schemaName) === 0;
    }
}

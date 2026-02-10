<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class DateHelper
{
    public static function isSameEntityDateWithSchemaDate(?\DateTime $entityDate, ?string $schemaDate): bool
    {
        return !empty($entityDate) && !empty($schemaDate) && $entityDate == date_create($schemaDate);
    }
}

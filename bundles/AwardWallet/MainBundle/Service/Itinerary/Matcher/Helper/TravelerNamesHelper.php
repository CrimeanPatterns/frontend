<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\Schema\Itineraries\Person;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @NoDI
 */
class TravelerNamesHelper
{
    public static function isSame(array $schemaPersons, array $entityTravelerNames): bool
    {
        $schemaNames = self::normalizeNames(array_map(fn (Person $person) => $person->name, $schemaPersons));
        $entityNames = self::normalizeNames($entityTravelerNames);

        return implode(',', $schemaNames) === implode(',', $entityNames);
    }

    /**
     * @param string[] $names
     */
    private static function normalizeNames(array $names): array
    {
        return it($names)
            ->map(fn (string $name) => self::normalizeName($name))
            ->unique()
            ->sort()
            ->toArray();
    }

    private static function normalizeName(string $name): string
    {
        return preg_replace('/[^\p{L}\p{N}\s\'-]/u', '', mb_strtolower($name));
    }
}

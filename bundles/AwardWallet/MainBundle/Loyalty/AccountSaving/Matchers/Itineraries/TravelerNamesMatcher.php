<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\Schema\Itineraries\Person;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class TravelerNamesMatcher
{
    /**
     * @param Person[] $schemaPersons
     * @param string[] $entityTravelerNames
     */
    public static function same(array $schemaPersons, array $entityTravelerNames): bool
    {
        $schemaNames = it($schemaPersons)
            ->map(fn (Person $person) => $person->name)
            ->unique()
            ->sort()
            ->joinToString(', ')
        ;

        $entityNames = it($entityTravelerNames)
            ->unique()
            ->sort()
            ->joinToString(', ')
        ;

        return strcasecmp($schemaNames, $entityNames) === 0;
    }
}

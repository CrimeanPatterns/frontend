<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\Itineraries;

use AwardWallet\Schema\Itineraries\Person;

class NamesMatcher
{
    private const SKIP_WORDS = [
        "cpa",
        "dds",
        "dr",
        "esq",
        "ii",
        "iii",
        "iv",
        "jd",
        "jr",
        "lld",
        "md",
        "miss",
        "mr",
        "mrs",
        "ms",
        "phd",
        "prof",
        "ret",
        "rn",
        "sr",
    ];

    /**
     * @param Person[] $persons
     * @param string[] $names
     */
    public function match(array $persons, array $names): bool
    {
        $personNames = array_map(function (Person $person) {
            return $this->extractFirstAndLastName($person->name);
        }, $persons);
        $personNames = array_filter($personNames, function (?string $name) {  return $name !== null; });

        $names = array_map([$this, "extractFirstAndLastName"], $names);
        $names = array_filter($names, function (?string $name) {  return $name !== null; });

        return count(array_intersect($names, $personNames)) > 0;
    }

    private function extractFirstAndLastName(string $name): ?string
    {
        $name = strtolower($name);
        $name = str_replace('.', '', $name);
        $name = preg_replace('#\s{2,}#ims', ' ', $name);
        $parts = explode(" ", $name);
        $parts = array_diff($parts, self::SKIP_WORDS);

        if (count($parts) < 2) {
            return null;
        }

        return array_shift($parts) . ' ' . array_pop($parts);
    }
}

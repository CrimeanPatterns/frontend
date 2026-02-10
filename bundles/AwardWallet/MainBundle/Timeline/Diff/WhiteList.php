<?php

namespace AwardWallet\MainBundle\Timeline\Diff;

use AwardWallet\MainBundle\Service\ItineraryFormatter\Constants\WhiteList as WhiteListProps;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList as P;

class WhiteList
{
    public static function shouldNotify(array $properties, array $propertiesOld, array $changedNames, int $currentTimestamp)
    {
        foreach ($changedNames as $sourceId => $names) {
            [$sourceCode] = explode(".", $sourceId);

            foreach ($names as $name) {
                if (self::inWhiteList([$name, $sourceCode . "." . $name])) {
                    if (($name === P::DEPARTURE_DATE || $name === P::ARRIVAL_DATE) && $sourceCode === 'S' && self::ignoreSmallDepDateChanges($name, $properties[$sourceId], $propertiesOld[$sourceId], $currentTimestamp)) {
                        continue;
                    }

                    return true;
                }
            }
        }

        return false;
    }

    private static function ignoreSmallDepDateChanges($name, Properties $props, Properties $propsOld, int $currentTimestamp)
    {
        /** @var Properties $props */
        $newValue = $props->values[$name];
        $oldValue = $propsOld->values[$name];

        return abs($newValue - $oldValue) < 15 * 60 && ($newValue - $currentTimestamp) <= 3600;
    }

    private static function inWhiteList($name)
    {
        return sizeof(array_intersect($name, WhiteListProps::LIST)) > 0;
    }
}

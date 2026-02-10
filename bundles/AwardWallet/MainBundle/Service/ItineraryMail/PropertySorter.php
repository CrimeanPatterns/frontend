<?php

namespace AwardWallet\MainBundle\Service\ItineraryMail;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Globals\ArrayHandler;

/**
 * @NoDI()
 */
class PropertySorter
{
    /**
     * @param Property[] $properties
     * @throws \Exception
     */
    public static function sort(array $properties, array $group)
    {
        $customSortCodes = [];

        foreach ($properties as $property) {
            $code = $property->getSortCode();

            if ($code !== null) {
                if (!isset($customSortCodes[$code])) {
                    $customSortCodes[$code] = [];
                }
                $customSortCodes[$code][] = $property->getCode();
            }
        }

        foreach ($customSortCodes as $code => $codes) {
            $index = array_search($code, $group);

            if ($index === false) {
                throw new \Exception("Unknown sort code: $code");
            }
            array_splice($group, $index + 1, 0, $codes);
        }

        $keyValues = [];

        foreach ($properties as $property) {
            $keyValues[$property->getCode()] = $property;
        }

        return ArrayHandler::smartSortArray($keyValues, $group, false);
    }
}

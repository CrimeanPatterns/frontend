<?php

namespace AwardWallet\MainBundle\Service\ItineraryComparator;

use AwardWallet\MainBundle\Service\ItineraryComparator\Property\DateTimeProperty;
use AwardWallet\MainBundle\Service\ItineraryComparator\Property\LocationProperty;
use AwardWallet\MainBundle\Service\ItineraryComparator\Property\NumberProperty;
use AwardWallet\MainBundle\Service\ItineraryComparator\Property\SimilarProperty;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;

class Comparator
{
    public function equals($value1, $value2, $name, $kind): bool
    {
        $value1 = Util::clean($value1);
        $value2 = Util::clean($value2);

        if (empty($value1) && empty($value2)) {
            return true;
        }

        $class = Property::class;
        /** @var Property $property */
        $property = $this->findProperty($class, $kind, $name);

        if (!isset($property)) {
            if (in_array($name, [
                PropertiesList::FLIGHT_CABIN_CLASS,
            ])) {
                $property = new SimilarProperty();
            } elseif (in_array($name, [
                PropertiesList::DEPARTURE_DATE,
                PropertiesList::ARRIVAL_DATE,
                PropertiesList::START_DATE,
                PropertiesList::END_DATE,
                PropertiesList::PICK_UP_DATE,
                PropertiesList::DROP_OFF_DATE,
                PropertiesList::CHECK_IN_DATE,
                PropertiesList::CHECK_OUT_DATE,
            ])) {
                $property = new DateTimeProperty();
            } elseif (in_array($name, [
                PropertiesList::SPENT_AWARDS,
                PropertiesList::EARNED_AWARDS,
                PropertiesList::TRAVELED_MILES,
                PropertiesList::KIDS_COUNT,
                PropertiesList::STOPS_COUNT,
                PropertiesList::GUEST_COUNT,
                PropertiesList::ROOM_COUNT,
                PropertiesList::FREE_NIGHTS,
            ])) {
                $property = new NumberProperty();
            } elseif (in_array($name, [
                PropertiesList::ADDRESS,
                PropertiesList::PICK_UP_LOCATION,
                PropertiesList::DROP_OFF_LOCATION,
                PropertiesList::DEPARTURE_ADDRESS,
                PropertiesList::ARRIVAL_ADDRESS,
            ])) {
                $property = new LocationProperty();
            } else {
                $property = new $class();
            }
        }

        return $property->equals($value1, $value2);
    }

    private function findProperty(string $baseNamespace, string $kind, string $propertyName): ?Property
    {
        $namespaces = [
            "{$baseNamespace}\\{$kind}\\{$propertyName}",
            "{$baseNamespace}\\{$propertyName}",
        ];

        foreach ($namespaces as $namespace) {
            if (class_exists($namespace)) {
                return new $namespace();
            }
        }

        return null;
    }
}

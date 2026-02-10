<?php

namespace AwardWallet\MainBundle\Service\ItineraryComparator\Property;

class LocationProperty extends SimilarProperty
{
    public function equals(string $value1, string $value2): bool
    {
        if (strcasecmp($value1, $value2) === 0) {
            return true;
        }

        $geoTag1 = FindGeoTag($value1);
        $geoTag2 = FindGeoTag($value2);

        if (!empty($geoTag1['Lat']) && !empty($geoTag1['Lng']) && !empty($geoTag2['Lat']) && !empty($geoTag2['Lng'])) {
            $distance = Distance($geoTag1['Lat'], $geoTag1['Lng'], $geoTag2['Lat'], $geoTag2['Lng']);

            return $distance <= 3;
        }

        return parent::equals($value1, $value2);
    }
}

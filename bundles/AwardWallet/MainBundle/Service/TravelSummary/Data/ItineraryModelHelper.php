<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Data;

use AwardWallet\MainBundle\Entity\Country;

class ItineraryModelHelper
{
    /**
     * Get the city name and state code for the United States, or the city name and country for all other countries.
     */
    public static function getLocation(Marker $marker): string
    {
        $parts[] = $marker->getCity();

        if ($marker->getCountryCode() === Country::US_CODE) {
            $state = $marker->getStateCode();

            if ($marker->getCity() !== $state && !is_numeric($state)) {
                $parts[] = $state;
            }

            if (empty($state)) {
                $parts[] = $marker->getCountry();
            }
        } else {
            $country = $marker->getCountry();

            if (!empty($country)) {
                $parts[] = $country;
            }
        }

        $parts = array_filter($parts);

        return implode(', ', $parts);
    }
}

<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Utils;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary as AbstractItineraryItem;
use AwardWallet\MainBundle\Timeline\Item\AbstractTrip as AbstractTripItem;

class TripHeaderResolver
{
    /**
     * Get the reservation title for when station codes are available.
     *
     * @param AbstractTripItem $item
     */
    public static function getTitle(AbstractItineraryItem $item): ?string
    {
        $title = null;
        $itinerary = $item->getSource();
        $tripInfo = $item->getTripInfo();

        if ($itinerary->getDepcode() !== null && $itinerary->getArrcode() !== null) {
            if (StringUtils::isNotEmpty($tripInfo->primaryTripNumberInfo->tripNumber ?? null)) {
                $title['scheduleNumber'] = $tripInfo->primaryTripNumberInfo->tripNumber;
            }

            $title['companyName'] = StringUtils::isNotEmpty($tripInfo->primaryTripNumberInfo->companyInfo->companyName ?? null) ?
                $tripInfo->primaryTripNumberInfo->companyInfo->companyName :
                '';
            $title['companyCode'] = StringUtils::isNotEmpty($tripInfo->primaryTripNumberInfo->companyInfo->companyCode ?? null) ?
                "({$tripInfo->primaryTripNumberInfo->companyInfo->companyCode})" :
                '';

            $title = trim(implode(' ', $title));
        }

        return $title;
    }

    /**
     * Get the departure and arrival station names for when station codes are not available.
     *
     * @param AbstractTripItem $item
     */
    public static function getStationNames(AbstractItineraryItem $item): array
    {
        $result = [];
        $itinerary = $item->getSource();

        if ($itinerary->getDepname() && $itinerary->getArrname()) {
            $result = [
                'departure' => $itinerary->getDepname(),
                'arrival' => $itinerary->getArrname(),
            ];
        } elseif ($itinerary->getDepgeotagid() && $itinerary->getArrgeotagid()) {
            $result = [
                'departure' => $itinerary->getDepgeotagid()->getAddress(),
                'arrival' => $itinerary->getArrgeotagid()->getAddress(),
            ];
        }

        return $result;
    }
}

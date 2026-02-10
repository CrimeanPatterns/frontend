<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;

/**
 * @NoDI
 */
class ObjectToArrayConverter
{
    public static function convertItinerary(Itinerary $itinerary): array
    {
        $result = [
            'id' => $itinerary->getIdString(),
            'kind' => $itinerary->getKind(),
            'confirmationNumber' => $itinerary->getConfirmationNumber(),
            'travelAgencyNumbers' => $itinerary->getTravelAgencyConfirmationNumbers(),
            'modified' => $itinerary->getModified(),
            'hidden' => $itinerary->getHidden(),
        ];

        if ($itinerary instanceof Trip) {
            $result['type'] = $itinerary->getType();
            $result['segments'] = array_map(function (Tripsegment $segment) {
                return self::convertTripSegment($segment);
            }, $itinerary->getSegments()->toArray());
        } elseif ($itinerary instanceof Restaurant) {
            $result['name'] = $itinerary->getName();
            $result['start'] = $itinerary->getStartdate()->format('c');
            $result['eventType'] = $itinerary->getEventtype();
        } elseif ($itinerary instanceof Rental) {
            $result['start'] = $itinerary->getPickupdatetime()->format('c');
            $result['end'] = $itinerary->getDropoffdatetime()->format('c');
            $result['company'] = $itinerary->getRentalCompanyName();
        } elseif ($itinerary instanceof Parking) {
            $result['start'] = $itinerary->getStartdate()->format('c');
            $result['end'] = $itinerary->getEnddate()->format('c');
            $result['company'] = $itinerary->getParkingCompanyName();
        } elseif ($itinerary instanceof Reservation) {
            $result['hotel'] = $itinerary->getHotelname();
            $result['start'] = $itinerary->getCheckindate()->format('c');
            $result['end'] = $itinerary->getCheckoutdate()->format('c');
        }

        return $result;
    }

    public static function convertTripSegment(Tripsegment $entitySegment): array
    {
        return [
            'id' => $entitySegment->getId(),
            'departure' => [
                'date' => $entitySegment->getDepartureDate() ? $entitySegment->getDepartureDate()->format('c') : null,
                'airport' => $entitySegment->getDepcode(),
                'name' => $entitySegment->getDepname(),
            ],
            'arrival' => [
                'date' => $entitySegment->getArrivalDate() ? $entitySegment->getArrivalDate()->format('c') : null,
                'airport' => $entitySegment->getArrcode(),
                'name' => $entitySegment->getArrname(),
            ],
            'flightNumber' => $entitySegment->getFlightNumber(),
            'marketingFlightNumber' => $entitySegment->getMarketingFlightNumber(),
            'operatingFlightNumber' => $entitySegment->getOperatingAirlineFlightNumber(),
            'marketingAirlineConfirmationNumber' => $entitySegment->getMarketingAirlineConfirmationNumber(),
            'operatingAirlineConfirmationNumber' => $entitySegment->getOperatingAirlineConfirmationNumber(),
            'status' => $entitySegment->getParsedStatus(),
            'type' => $entitySegment->getType(),
        ];
    }
}

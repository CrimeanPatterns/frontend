<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class Reservation.
 *
 * @property $hotelName
 * @property $chainName
 * @property $checkInDate
 * @property $checkOutDate
 * @property $address
 * @property $phone
 * @property $fax
 * @property $guests
 * @property $guestCount
 * @property $kidsCount
 * @property $rooms
 * @property $roomsCount
 * @property $cancellationPolicy
 */
class HotelReservation extends Itinerary
{
    /**
     * @var string
     * @Type("string")
     */
    protected $hotelName;
    /**
     * @var string
     * @Type("string")
     */
    protected $chainName;
    /**
     * @var string
     * @Type("string")
     */
    protected $checkInDate;
    /**
     * @var string
     * @Type("string")
     */
    protected $checkOutDate;
    /**
     * @var Address
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\Itineraries\Address")
     */
    protected $address;
    /**
     * @var string
     * @Type("string")
     */
    protected $phone;
    /**
     * @var string
     * @Type("string")
     */
    protected $fax;
    /**
     * @var Person[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\Itineraries\Person>")
     */
    protected $guests;
    /**
     * @var int
     * @Type("integer")
     */
    protected $guestCount;
    /**
     * @var int
     * @Type("integer")
     */
    protected $kidsCount;
    /**
     * @var Room[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\Itineraries\Room>")
     */
    protected $rooms;
    /**
     * @var int
     * @Type("integer")
     */
    protected $roomsCount;
    /**
     * @var string
     * @Type("string")
     */
    protected $cancellationPolicy;
}

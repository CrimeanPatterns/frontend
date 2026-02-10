<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class Rental.
 *
 * @property $pickup
 * @property $dropoff
 * @property $car
 * @property $rentalCompany
 * @property $driver
 * @property $promoCode
 * @property $serviceLevel
 * @property $pricedEquipment
 * @property $discount
 * @property $discounts
 * @property $paymentMethod
 */
class CarRental extends Itinerary
{
    /**
     * @var CarRentalPoint
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\Itineraries\CarRentalPoint")
     */
    protected $pickup;
    /**
     * @var CarRentalPoint
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\Itineraries\CarRentalPoint")
     */
    protected $dropoff;
    /**
     * @var Car
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\Itineraries\Car")
     */
    protected $car;
    /**
     * @var Fee[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\Itineraries\Fee>")
     */
    protected $pricedEquipment;
    /**
     * @var CarRentalDiscount[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\Itineraries\CarRentalDiscount>")
     */
    protected $discounts;
    /**
     * @var string
     * @Type("string")
     */
    protected $rentalCompany;
    /**
     * @var Person
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\Itineraries\Person")
     */
    protected $driver;
    /**
     * @var string
     * @Type("string")
     */
    protected $promoCode;
    /**
     * @var string
     * @Type("string")
     */
    protected $serviceLevel;
    /**
     * @var string
     * @Type("string")
     */
    protected $paymentMethod;
}

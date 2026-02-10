<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class Price
{
    /**
     * @var float
     */
    private $total;
    /**
     * @var float
     */
    private $lat;
    /**
     * @var float
     */
    private $lng;
    /**
     * @var string
     */
    private $hotelUrl;
    /**
     * @var string
     */
    private $bookingUrl;
    /**
     * @var string
     */
    private $hotelName;
    private string $address;

    public function __construct(float $total, float $lat, float $lng, string $hotelUrl, string $bookingUrl, string $hotelName, string $address)
    {
        $this->total = $total;
        $this->lat = $lat;
        $this->lng = $lng;
        $this->hotelUrl = $hotelUrl;
        $this->bookingUrl = $bookingUrl;
        $this->hotelName = $hotelName;
        $this->address = $address;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function getLat(): float
    {
        return $this->lat;
    }

    public function getLng(): float
    {
        return $this->lng;
    }

    public function getHotelUrl(): string
    {
        return $this->hotelUrl;
    }

    public function getBookingUrl(): string
    {
        return $this->bookingUrl;
    }

    public function getHotelName(): string
    {
        return $this->hotelName;
    }

    public function getAddress(): string
    {
        return $this->address;
    }
}

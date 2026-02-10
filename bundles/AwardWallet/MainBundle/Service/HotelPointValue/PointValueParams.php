<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\HotelBrand;
use AwardWallet\MainBundle\Entity\Reservation;

/**
 * @NoDI()
 */
class PointValueParams
{
    /**
     * @var int
     */
    private $spentAwards;
    /**
     * @var int
     */
    private $kidsCount;
    /**
     * @var int
     */
    private $roomsCount;
    /**
     * @var string
     */
    private $currencyCode;
    /**
     * @var float|int
     */
    private $total;
    /**
     * @var string
     */
    private $hash;
    /**
     * @var string
     */
    private $hotelName;
    /**
     * @var float
     */
    private $lat;
    /**
     * @var float
     */
    private $lng;
    /**
     * @var \DateTime
     */
    private $checkinDate;
    /**
     * @var \DateTime
     */
    private $checkoutDate;
    /**
     * @var int|null
     */
    private $guestCount;

    private ?HotelBrand $brand;

    public function __construct(Reservation $reservation, int $spentAwards, ?HotelBrand $brand)
    {
        $this->hotelName = $reservation->getHotelname();
        $this->lat = $reservation->getGeotagid()->getLat();
        $this->lng = $reservation->getGeotagid()->getLng();
        $this->checkinDate = $reservation->getCheckindate();
        $this->checkoutDate = $reservation->getCheckoutdate();
        $this->spentAwards = $spentAwards;
        $this->guestCount = $reservation->getGuestCount();
        $this->kidsCount = $reservation->getKidsCount() ?? 0;
        $this->roomsCount = $reservation->getRoomCount() ?? 1;
        $this->currencyCode = $reservation->getPricingInfo()->getCurrencyCode() ?? 'USD';
        $this->total = $reservation->getPricingInfo()->getTotal() ?? 0;
        $this->hash = $this->calcHash($reservation);
        $this->brand = $brand;
    }

    public function getSpentAwards(): int
    {
        return $this->spentAwards;
    }

    public function getKidsCount(): int
    {
        return $this->kidsCount;
    }

    public function getRoomsCount(): int
    {
        return $this->roomsCount;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return float|int
     */
    public function getTotal()
    {
        return $this->total;
    }

    public function getHotelName(): string
    {
        return $this->hotelName;
    }

    public function getLat(): float
    {
        return $this->lat;
    }

    public function getLng(): float
    {
        return $this->lng;
    }

    public function getCheckinDate(): \DateTime
    {
        return $this->checkinDate;
    }

    public function getCheckoutDate(): \DateTime
    {
        return $this->checkoutDate;
    }

    public function setCheckinDate(\DateTime $checkinDate): void
    {
        $this->checkinDate = $checkinDate;
    }

    public function setCheckoutDate(\DateTime $checkoutDate): void
    {
        $this->checkoutDate = $checkoutDate;
    }

    public function setGuestCount(?int $guestCount): void
    {
        $this->guestCount = $guestCount;
    }

    public function getGuestCount(): ?int
    {
        return $this->guestCount;
    }

    public function getBrand(): ?HotelBrand
    {
        return $this->brand;
    }

    private function calcHash(Reservation $reservation): string
    {
        return md5(
            $reservation->getHotelname()
            . sprintf('%02.4f', $reservation->getGeotagid()->getLat())
            . sprintf('%02.4f', $reservation->getGeotagid()->getLng())
            . $reservation->getCheckindate()->format("Y-m-d")
            . $reservation->getCheckoutdate()->format("Y-m-d")
            . $reservation->getGuestCount()
            . $this->kidsCount
            . $this->roomsCount
            . $this->currencyCode
            . $this->spentAwards
            . sprintf('%02.2f', $this->total)
        );
    }
}

<?php

namespace AwardWallet\MainBundle\Timeline\Item;

class BookingHotelForm implements \JsonSerializable
{
    private string $destination;

    private \DateTime $checkinDate;

    private \DateTime $checkoutDate;

    private string $url;

    public function __construct(string $destination, \DateTime $checkinDate, \DateTime $checkoutDate, string $url)
    {
        $this->destination = $destination;
        $this->checkinDate = $checkinDate;
        $this->checkoutDate = $checkoutDate;
        $this->url = $url;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function getCheckinDate(): string
    {
        return $this->checkinDate->format('Y-m-d');
    }

    public function getCheckoutDate(): string
    {
        return $this->checkoutDate->format('Y-m-d');
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function jsonSerialize()
    {
        return [
            'destination' => $this->destination,
            'checkinDate' => $this->getCheckinDate(),
            'checkoutDate' => $this->getCheckoutDate(),
            'url' => $this->url,
        ];
    }
}

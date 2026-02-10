<?php

namespace AwardWallet\MainBundle\Timeline\Item;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Menu\AlternativeFlights;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Geofence;

class AirTrip extends AbstractTrip
{
    protected string $bookingInfo;

    protected string $bookingUrl;

    protected ?BookingHotelForm $bookingFormFields = null;

    protected ?AlternativeFlights $tripAlternatives = null;

    /**
     * @var Geofence[]
     */
    protected array $geofences = [];

    private bool $isMonitoringLowPrices = false;

    public function __construct(Tripsegment $tripsegment, ?Provider $provider = null)
    {
        parent::__construct($tripsegment, $provider);

        $iataPattern = Tripsegment::IATA_PATTERN;

        if (preg_match($iataPattern, $tripsegment->getDepcode()) && preg_match($iataPattern, $tripsegment->getArrcode())) {
            $this->setMap(
                new Map([$tripsegment->getDepcode(), $tripsegment->getArrcode()], $tripsegment->getArrivalDate())
            );
        }
    }

    public function getBookingInfo(): ?string
    {
        return $this->bookingInfo;
    }

    public function setBookingInfo(string $bookingInfo): self
    {
        $this->bookingInfo = $bookingInfo;

        return $this;
    }

    public function getBookingUrl(): ?string
    {
        return $this->bookingUrl;
    }

    public function setBookingUrl(string $bookingUrl): self
    {
        $this->bookingUrl = $bookingUrl;

        return $this;
    }

    public function getBookingFormFields(): ?BookingHotelForm
    {
        return $this->bookingFormFields;
    }

    public function setBookingFormFields(BookingHotelForm $bookingFormFields): self
    {
        $this->bookingFormFields = $bookingFormFields;

        return $this;
    }

    public function getTripAlternatives(): ?AlternativeFlights
    {
        return $this->tripAlternatives;
    }

    public function setTripAlternatives(AlternativeFlights $tripAlternatives): AirTrip
    {
        $this->tripAlternatives = $tripAlternatives;

        return $this;
    }

    /**
     * @return Geofence[]
     */
    public function getGeofences(): array
    {
        return $this->geofences;
    }

    /**
     * @param Geofence[] $geofences
     */
    public function setGeofences(array $geofences): self
    {
        $this->geofences = $geofences;

        return $this;
    }

    public function addGeofence(Geofence $geofence): self
    {
        $this->geofences[] = $geofence;

        return $this;
    }

    public function isMonitoringLowPrices(): bool
    {
        return $this->isMonitoringLowPrices;
    }

    public function setMonitoringLowPrices(bool $isMonitoringLowPrices): self
    {
        $this->isMonitoringLowPrices = $isMonitoringLowPrices;

        return $this;
    }

    public function getIcon(): string
    {
        /** @var Tripsegment $source */
        $source = $this->getSource();
        $icon = Icon::FLY;

        if ($source && ($aircraft = $source->getAircraft())) {
            $icon .= ' ' . $aircraft->getIcon();
        }

        return $icon;
    }

    public function getType(): string
    {
        return Type::AIR_TRIP;
    }
}

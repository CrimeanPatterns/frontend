<?php

namespace AwardWallet\MainBundle\Timeline;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Globals\StringUtils;

class PhonesSection
{
    public const SECTION_ACCOUNT = 'account';
    public const SECTION_MARKETING_AIRLINE = 'marketing_airline';
    public const SECTION_TRAVEL_AGENCY = 'travel_agency';
    public const SECTION_OPERATING_AIRLINE = 'operating_airline';
    public const SECTION_ISSUING_AIRLINE = 'issuing_airline';
    /**
     * @var Provider|ProviderAirlinePair|string|Airline|Itinerary
     */
    private $origin;
    /**
     * @var array
     */
    private $addressBookPhones;
    /**
     * @var array
     */
    private $localPhones;

    /**
     * @param string|Provider|Airline|ProviderAirlinePair $origin
     */
    public function __construct($origin, array $addressBookPhones, array $localPhones)
    {
        if (\is_string($origin)) {
            if (StringUtils::isEmpty($origin)) {
                throw new \InvalidArgumentException('Origin should not be empty');
            }
        } elseif (
            !($origin instanceof Provider)
            && !($origin instanceof ProviderAirlinePair)
            && !($origin instanceof Airline)
            && !($origin instanceof Itinerary)
        ) {
            throw new \InvalidArgumentException(sprintf('Origin should be instanceof %s, %s, %s, %s or string', Provider::class, ProviderAirlinePair::class, Airline::class, Itinerary::class));
        }

        $this->origin = $origin;
        $this->addressBookPhones = $addressBookPhones;

        // convert to generalized format
        foreach ($localPhones as $idx => $localPhone) {
            if (!\is_array($localPhone)) {
                $localPhones[$idx] = ['Phone' => $localPhone];
            }
        }

        $this->localPhones = $localPhones;
    }

    public function getName(): ?string
    {
        if (\is_string($this->origin)) {
            return $this->origin;
        } elseif ($this->origin instanceof ProviderAirlinePair) {
            return $this->origin->getAirline()->getName();
        } elseif ($this->origin instanceof Airline) {
            return $this->origin->getName();
        } elseif ($this->origin instanceof Rental) {
            return $this->origin->getRentalCompanyName();
        } elseif ($this->origin instanceof Reservation) {
            return $this->origin->getHotelname();
        } elseif ($this->origin instanceof Restaurant) {
            return $this->origin->getName();
        } elseif ($this->origin instanceof Parking) {
            return $this->origin->getParkingCompanyName();
        } elseif ($this->origin instanceof Provider) {
            return $this->origin->getShortname();
        }

        return null;
    }

    /**
     * @return Provider|ProviderAirlinePair|string
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    public function getAddressBookPhones(): array
    {
        return $this->addressBookPhones;
    }

    public function getLocalPhones(): array
    {
        return $this->localPhones;
    }
}

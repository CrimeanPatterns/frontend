<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Data;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * Class showing the start or end point on the map.
 *
 * @NoDI()
 */
class Marker
{
    /**
     * City name.
     */
    private ?string $city = null;
    /**
     * Country name.
     */
    private ?string $country = null;
    /**
     * Two-letter state code.
     */
    private ?string $stateCode = null;
    /**
     * Two-letter country code.
     */
    private ?string $countryCode = null;
    /**
     * Full address of the reservation.
     */
    private ?string $address = null;
    /**
     * Geographic latitude.
     */
    private float $latitude;
    /**
     * Geographic longitude.
     */
    private float $longitude;
    /**
     * Time zone in which the point is located.
     */
    private string $timeZone;
    /**
     * The reservation category used to display the desired icon.
     */
    private string $category;
    /**
     * Departure or arrival location code (used for flights).
     */
    private ?string $airCode = null;
    /**
     * Names of the location of departure or arrival, such as railroad stations.
     */
    private ?string $locationName = null;
    /**
     * List of segments related to this marker that are displayed in the modal window.
     *
     * @var TripSegment[]
     */
    private array $segments = [];
    /**
     * A list of routes for trips.
     *
     * @see https://developers.google.com/maps/documentation/directions
     * @var Route[]
     */
    private array $directions = [];
    /**
     * A flag indicating whether there are segments with different names in the marker. If the names are different,
     * the full address will be displayed in the modal window header.
     */
    private bool $differentTitles = false;

    public function __construct(float $latitude, float $longitude, string $timeZone, string $category)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->timeZone = $timeZone;
        $this->category = $category;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getStateCode(): ?string
    {
        return $this->stateCode;
    }

    public function setStateCode(?string $stateCode): self
    {
        $this->stateCode = $stateCode;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function getTimeZone(): string
    {
        return $this->timeZone;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getAirCode(): ?string
    {
        return $this->airCode;
    }

    public function setAirCode(?string $airCode): self
    {
        $this->airCode = $airCode;

        return $this;
    }

    public function getLocationName(): ?string
    {
        return $this->locationName;
    }

    public function setLocationName(?string $locationName): self
    {
        $this->locationName = $locationName;

        return $this;
    }

    public function getSegments(): array
    {
        return $this->segments;
    }

    /**
     * @param TripSegment[] $segments
     */
    public function setSegments(array $segments): self
    {
        $this->segments = $segments;

        return $this;
    }

    public function addSegment(TripSegment $segment): self
    {
        $this->segments[] = $segment;

        return $this;
    }

    public function getDirections(): array
    {
        return $this->directions;
    }

    public function addDirection(Route $direction): self
    {
        $this->directions[] = $direction;

        return $this;
    }

    public function isDifferentTitles(): bool
    {
        return $this->differentTitles;
    }

    public function setDifferentTitles(bool $differentTitles): self
    {
        $this->differentTitles = $differentTitles;

        return $this;
    }
}

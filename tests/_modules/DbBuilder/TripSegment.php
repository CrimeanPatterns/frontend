<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class TripSegment extends AbstractDbEntity
{
    private ?Trip $trip;

    private ?GeoTag $depGeoTag = null;

    private ?GeoTag $arrGeoTag = null;
    /**
     * Код аэропорта отправления.
     */
    private ?AirCode $depAirCode = null;
    /**
     * Код аэропорта прибытия.
     */
    private ?AirCode $arrAirCode = null;
    /**
     * Название авиакомпании, указанной в билете.
     */
    private ?Airline $airline = null;

    private ?Airline $operatingAirline = null;

    private ?Airline $wetLeaseAirline = null;

    public function __construct(
        ?string $depCode,
        string $depName,
        \DateTimeInterface $depDate,
        ?string $arrCode,
        string $arrName,
        \DateTimeInterface $arrDate,
        ?Trip $trip = null,
        array $fields = []
    ) {
        parent::__construct(array_merge([
            'ScheduledDepDate' => $depDate->format('Y-m-d H:i:s'),
            'ScheduledArrDate' => $arrDate->format('Y-m-d H:i:s'),
        ], $fields, [
            'DepCode' => $depCode,
            'DepName' => $depName,
            'DepDate' => $depDate->format('Y-m-d H:i:s'),
            'ArrCode' => $arrCode,
            'ArrName' => $arrName,
            'ArrDate' => $arrDate->format('Y-m-d H:i:s'),
        ]));

        $this->trip = $trip;
    }

    public function getTrip(): ?Trip
    {
        return $this->trip;
    }

    public function setTrip(?Trip $trip): self
    {
        $this->trip = $trip;

        return $this;
    }

    public function getDepGeoTag(): ?GeoTag
    {
        return $this->depGeoTag;
    }

    public function setDepGeoTag(?GeoTag $depGeoTag): self
    {
        $this->depGeoTag = $depGeoTag;

        return $this;
    }

    public function getArrGeoTag(): ?GeoTag
    {
        return $this->arrGeoTag;
    }

    public function setArrGeoTag(?GeoTag $arrGeoTag): self
    {
        $this->arrGeoTag = $arrGeoTag;

        return $this;
    }

    public function getDepAirCode(): ?AirCode
    {
        return $this->depAirCode;
    }

    public function setDepAirCode(?AirCode $airCode): self
    {
        $this->depAirCode = $airCode;

        return $this;
    }

    public function getArrAirCode(): ?AirCode
    {
        return $this->arrAirCode;
    }

    public function setArrAirCode(?AirCode $airCode): self
    {
        $this->arrAirCode = $airCode;

        return $this;
    }

    public function getAirline(): ?Airline
    {
        return $this->airline;
    }

    public function setAirline(?Airline $airline): self
    {
        $this->airline = $airline;

        return $this;
    }

    public function getOperatingAirline(): ?Airline
    {
        return $this->operatingAirline;
    }

    public function setOperatingAirline(?Airline $operatingAirline): self
    {
        $this->operatingAirline = $operatingAirline;

        return $this;
    }

    public function getWetLeaseAirline(): ?Airline
    {
        return $this->wetLeaseAirline;
    }

    public function setWetLeaseAirline(?Airline $wetLeaseAirline): self
    {
        $this->wetLeaseAirline = $wetLeaseAirline;

        return $this;
    }
}

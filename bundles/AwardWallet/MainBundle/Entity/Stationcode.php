<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Stationcode.
 *
 * @ORM\Table(name="StationCode")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\StationcodeRepository")
 * @JMS\ExclusionPolicy("All")
 */
class Stationcode
{
    public const TYPE_RAIL = 'rail';
    public const TYPE_BUS = 'bus';

    /**
     * @var int
     * @ORM\Column(name="StationCodeID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $stationcodeid; // no in json

    /**
     * @var string
     * @JMS\Expose()
     * @JMS\Groups({"basic"})
     * @ORM\Column(name="StationCode", type="string", length=3, nullable=false)
     */
    private $stationcode;

    /**
     * @var string
     * @JMS\Expose()
     * @JMS\Groups({"basic"})
     * @ORM\Column(name="StationName", type="string", length=80, nullable=true)
     */
    private $stationname; // name

    /**
     * @var string
     * @ORM\Column(name="AlternateNames", type="string", length=1000, nullable=true)
     */
    private $alternatenames;

    /**
     * @var string
     * @ORM\Column(name="CityCode", type="string", length=3, nullable=false)
     */
    private $citycode; // city_code

    /**
     * @var string
     * @ORM\Column(name="CountryCode", type="string", length=3, nullable=false)
     */
    private $countrycode; // country_code

    /**
     * @ORM\Column(name="IcaoCode", type="string", length=4, nullable=true)
     */
    private $icaoCode; // icao

    /**
     * @ORM\Column(name="CityName", type="string", length=40, nullable=true)
     */
    private $cityName;

    /**
     * @ORM\Column(name="AddressLine", type="string", length=255, nullable=true)
     */
    private $addressLine;

    /**
     * @ORM\Column(name="State", type="string", length=4, nullable=true)
     */
    private $stateCode;

    /**
     * @ORM\Column(name="StateName", type="string", length=100, nullable=true)
     */
    private $stateName;

    /**
     * @ORM\Column(name="Country", type="string", length=100, nullable=true)
     */
    private $country;

    /**
     * @ORM\Column(name="PostalCode", type="string", length=40, nullable=true)
     */
    private $postalCode;

    /**
     * @var float
     * @ORM\Column(name="Lat", type="float", nullable=true)
     */
    private $lat = 0;

    /**
     * @var float
     * @ORM\Column(name="Lng", type="float", nullable=true)
     */
    private $lng = 0;

    /**
     * @var float
     * @ORM\Column(name="LatOriginal", type="float", nullable=true)
     */
    private $latOriginal = 0;

    /**
     * @var float
     * @ORM\Column(name="LngOriginal", type="float", nullable=true)
     */
    private $lngOriginal = 0;

    /**
     * @var string
     * @ORM\Column(name="TimeZoneLocation", type="string", length=64, nullable=false)
     */
    private $timeZoneLocation = 'UTC';

    /**
     * @var \DateTime
     * @ORM\Column(name="LastUpdateDate", type="datetime", nullable=true)
     */
    private $lastupdatedate; // no in json

    /**
     * @var int
     * @ORM\Column(name="Gmt", type="integer", nullable=false)
     */
    private $gmt = 0;

    /**
     * @var string
     * @ORM\Column(name="StationType", type="string", length=64, nullable=false)
     */
    private $type; // bus | rail

    public function getStationcodeid(): int
    {
        return $this->stationcodeid;
    }

    public function setStationcodeid(int $stationcodeid): Stationcode
    {
        $this->stationcodeid = $stationcodeid;

        return $this;
    }

    public function getStationcode(): string
    {
        return $this->stationcode;
    }

    public function setStationcode(string $stationcode): Stationcode
    {
        $this->stationcode = $stationcode;

        return $this;
    }

    public function getStationname(): string
    {
        return $this->stationname;
    }

    public function setStationname(string $stationname): Stationcode
    {
        $this->stationname = $stationname;

        return $this;
    }

    public function getAlternatenames(): string
    {
        return $this->alternatenames;
    }

    public function setAlternatenames(string $alternatenames): Stationcode
    {
        $this->alternatenames = $alternatenames;

        return $this;
    }

    public function getCitycode(): string
    {
        return $this->citycode;
    }

    public function setCitycode(string $citycode): Stationcode
    {
        $this->citycode = $citycode;

        return $this;
    }

    public function getCountrycode(): string
    {
        return $this->countrycode;
    }

    public function setCountrycode(string $countrycode): Stationcode
    {
        $this->countrycode = $countrycode;

        return $this;
    }

    public function getIcaoCode()
    {
        return $this->icaoCode;
    }

    /**
     * @return Stationcode
     */
    public function setIcaoCode($icaoCode)
    {
        $this->icaoCode = $icaoCode;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * @param float $lat
     */
    public function setLat($lat): Stationcode
    {
        $this->lat = $lat;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getLng()
    {
        return $this->lng;
    }

    /**
     * @param float $lng
     */
    public function setLng($lng): Stationcode
    {
        $this->lng = $lng;

        return $this;
    }

    public function getTimeZoneLocation(): string
    {
        return $this->timeZoneLocation;
    }

    public function setTimeZoneLocation(string $timeZoneLocation): self
    {
        $this->timeZoneLocation = $timeZoneLocation;

        return $this;
    }

    public function getDateTimeZone(): \DateTimeZone
    {
        try {
            return new \DateTimeZone($this->getTimeZoneLocation());
        } catch (\Exception $e) {
            return new \DateTimeZone('UTC');
        }
    }

    public function getLastupdatedate(): \DateTime
    {
        return $this->lastupdatedate;
    }

    public function setLastupdatedate(\DateTime $lastupdatedate): Stationcode
    {
        $this->lastupdatedate = $lastupdatedate;

        return $this;
    }

    public function getGmt(): int
    {
        return $this->gmt;
    }

    public function setGmt(int $gmt): Stationcode
    {
        $this->gmt = $gmt;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): Stationcode
    {
        $this->type = $type;

        return $this;
    }

    public function isBus(): bool
    {
        return $this->type === self::TYPE_BUS;
    }

    public function isRail(): bool
    {
        return $this->type === self::TYPE_RAIL;
    }

    public function getCityName()
    {
        return $this->cityName;
    }

    /**
     * @return Stationcode
     */
    public function setCityName($cityName)
    {
        $this->cityName = $cityName;

        return $this;
    }

    public function getAddressLine()
    {
        return $this->addressLine;
    }

    /**
     * @return Stationcode
     */
    public function setAddressLine($addressLine)
    {
        $this->addressLine = $addressLine;

        return $this;
    }

    public function getStateCode()
    {
        return $this->stateCode;
    }

    /**
     * @return Stationcode
     */
    public function setStateCode($stateCode)
    {
        $this->stateCode = $stateCode;

        return $this;
    }

    public function getStateName()
    {
        return $this->stateName;
    }

    /**
     * @return Stationcode
     */
    public function setStateName($stateName)
    {
        $this->stateName = $stateName;

        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return Stationcode
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * @return Stationcode
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getLatOriginal(): float
    {
        return $this->latOriginal;
    }

    public function setLatOriginal(float $latOriginal): Stationcode
    {
        $this->latOriginal = $latOriginal;

        return $this;
    }

    public function getLngOriginal(): float
    {
        return $this->lngOriginal;
    }

    public function setLngOriginal(float $lngOriginal): Stationcode
    {
        $this->lngOriginal = $lngOriginal;

        return $this;
    }
}

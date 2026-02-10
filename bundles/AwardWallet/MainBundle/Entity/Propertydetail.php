<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Propertydetail.
 *
 * @ORM\Table(name="PropertyDetail")
 * @ORM\Entity
 */
class Propertydetail
{
    /**
     * @var int
     * @ORM\Column(name="PropertyId", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $propertyid;

    /**
     * @var string
     * @ORM\Column(name="PropertyName", type="string", length=80, nullable=true)
     */
    protected $propertyname;

    /**
     * @var string
     * @ORM\Column(name="PropertyHeadline", type="string", length=80, nullable=true)
     */
    protected $propertyheadline;

    /**
     * @var string
     * @ORM\Column(name="PropertyDescription", type="string", length=80, nullable=true)
     */
    protected $propertydescription;

    /**
     * @var int
     * @ORM\Column(name="StarRating", type="integer", nullable=true)
     */
    protected $starrating;

    /**
     * @var string
     * @ORM\Column(name="DisplayPhoto", type="string", length=80, nullable=true)
     */
    protected $displayphoto;

    /**
     * @var string
     * @ORM\Column(name="CurrencyCode", type="string", length=10, nullable=true)
     */
    protected $currencycode;

    /**
     * @var string
     * @ORM\Column(name="ChainCode", type="string", length=10, nullable=true)
     */
    protected $chaincode;

    /**
     * @var string
     * @ORM\Column(name="ChainName", type="string", length=80, nullable=true)
     */
    protected $chainname;

    /**
     * @var int
     * @ORM\Column(name="YearOpened", type="integer", nullable=true)
     */
    protected $yearopened;

    /**
     * @var int
     * @ORM\Column(name="YearRenovated", type="integer", nullable=true)
     */
    protected $yearrenovated;

    /**
     * @var string
     * @ORM\Column(name="AirportText", type="string", length=80, nullable=true)
     */
    protected $airporttext;

    /**
     * @var string
     * @ORM\Column(name="AreasServed", type="string", length=80, nullable=true)
     */
    protected $areasserved;

    /**
     * @var string
     * @ORM\Column(name="LocationDescription", type="string", length=80, nullable=true)
     */
    protected $locationdescription;

    /**
     * @var string
     * @ORM\Column(name="CheckIn", type="string", length=80, nullable=true)
     */
    protected $checkin;

    /**
     * @var string
     * @ORM\Column(name="CheckOut", type="string", length=80, nullable=true)
     */
    protected $checkout;

    /**
     * @var int
     * @ORM\Column(name="NumberOfFloors", type="integer", nullable=true)
     */
    protected $numberoffloors;

    /**
     * @var int
     * @ORM\Column(name="NumberOfRooms", type="integer", nullable=true)
     */
    protected $numberofrooms;

    /**
     * @var int
     * @ORM\Column(name="NumberOfSuites", type="integer", nullable=true)
     */
    protected $numberofsuites;

    /**
     * @var string
     * @ORM\Column(name="PhoneNumber", type="string", length=80, nullable=true)
     */
    protected $phonenumber;

    /**
     * @var string
     * @ORM\Column(name="FaxNumber", type="string", length=80, nullable=true)
     */
    protected $faxnumber;

    /**
     * @var float
     * @ORM\Column(name="Latitude", type="float", nullable=true)
     */
    protected $latitude;

    /**
     * @var float
     * @ORM\Column(name="Longitude", type="float", nullable=true)
     */
    protected $longitude;

    /**
     * @var string
     * @ORM\Column(name="Address1", type="string", length=80, nullable=true)
     */
    protected $address1;

    /**
     * @var string
     * @ORM\Column(name="Address2", type="string", length=80, nullable=true)
     */
    protected $address2;

    /**
     * @var string
     * @ORM\Column(name="CityName", type="string", length=80, nullable=true)
     */
    protected $cityname;

    /**
     * @var string
     * @ORM\Column(name="StateCode", type="string", length=10, nullable=true)
     */
    protected $statecode;

    /**
     * @var string
     * @ORM\Column(name="Zip", type="string", length=20, nullable=true)
     */
    protected $zip;

    /**
     * @var string
     * @ORM\Column(name="CountryCode", type="string", length=10, nullable=true)
     */
    protected $countrycode;

    /**
     * Get propertyid.
     *
     * @return int
     */
    public function getPropertyid()
    {
        return $this->propertyid;
    }

    /**
     * Set propertyname.
     *
     * @param string $propertyname
     * @return Propertydetail
     */
    public function setPropertyname($propertyname)
    {
        $this->propertyname = $propertyname;

        return $this;
    }

    /**
     * Get propertyname.
     *
     * @return string
     */
    public function getPropertyname()
    {
        return $this->propertyname;
    }

    /**
     * Set propertyheadline.
     *
     * @param string $propertyheadline
     * @return Propertydetail
     */
    public function setPropertyheadline($propertyheadline)
    {
        $this->propertyheadline = $propertyheadline;

        return $this;
    }

    /**
     * Get propertyheadline.
     *
     * @return string
     */
    public function getPropertyheadline()
    {
        return $this->propertyheadline;
    }

    /**
     * Set propertydescription.
     *
     * @param string $propertydescription
     * @return Propertydetail
     */
    public function setPropertydescription($propertydescription)
    {
        $this->propertydescription = $propertydescription;

        return $this;
    }

    /**
     * Get propertydescription.
     *
     * @return string
     */
    public function getPropertydescription()
    {
        return $this->propertydescription;
    }

    /**
     * Set starrating.
     *
     * @param int $starrating
     * @return Propertydetail
     */
    public function setStarrating($starrating)
    {
        $this->starrating = $starrating;

        return $this;
    }

    /**
     * Get starrating.
     *
     * @return int
     */
    public function getStarrating()
    {
        return $this->starrating;
    }

    /**
     * Set displayphoto.
     *
     * @param string $displayphoto
     * @return Propertydetail
     */
    public function setDisplayphoto($displayphoto)
    {
        $this->displayphoto = $displayphoto;

        return $this;
    }

    /**
     * Get displayphoto.
     *
     * @return string
     */
    public function getDisplayphoto()
    {
        return $this->displayphoto;
    }

    /**
     * Set currencycode.
     *
     * @param string $currencycode
     * @return Propertydetail
     */
    public function setCurrencycode($currencycode)
    {
        $this->currencycode = $currencycode;

        return $this;
    }

    /**
     * Get currencycode.
     *
     * @return string
     */
    public function getCurrencycode()
    {
        return $this->currencycode;
    }

    /**
     * Set chaincode.
     *
     * @param string $chaincode
     * @return Propertydetail
     */
    public function setChaincode($chaincode)
    {
        $this->chaincode = $chaincode;

        return $this;
    }

    /**
     * Get chaincode.
     *
     * @return string
     */
    public function getChaincode()
    {
        return $this->chaincode;
    }

    /**
     * Set chainname.
     *
     * @param string $chainname
     * @return Propertydetail
     */
    public function setChainname($chainname)
    {
        $this->chainname = $chainname;

        return $this;
    }

    /**
     * Get chainname.
     *
     * @return string
     */
    public function getChainname()
    {
        return $this->chainname;
    }

    /**
     * Set yearopened.
     *
     * @param int $yearopened
     * @return Propertydetail
     */
    public function setYearopened($yearopened)
    {
        $this->yearopened = $yearopened;

        return $this;
    }

    /**
     * Get yearopened.
     *
     * @return int
     */
    public function getYearopened()
    {
        return $this->yearopened;
    }

    /**
     * Set yearrenovated.
     *
     * @param int $yearrenovated
     * @return Propertydetail
     */
    public function setYearrenovated($yearrenovated)
    {
        $this->yearrenovated = $yearrenovated;

        return $this;
    }

    /**
     * Get yearrenovated.
     *
     * @return int
     */
    public function getYearrenovated()
    {
        return $this->yearrenovated;
    }

    /**
     * Set airporttext.
     *
     * @param string $airporttext
     * @return Propertydetail
     */
    public function setAirporttext($airporttext)
    {
        $this->airporttext = $airporttext;

        return $this;
    }

    /**
     * Get airporttext.
     *
     * @return string
     */
    public function getAirporttext()
    {
        return $this->airporttext;
    }

    /**
     * Set areasserved.
     *
     * @param string $areasserved
     * @return Propertydetail
     */
    public function setAreasserved($areasserved)
    {
        $this->areasserved = $areasserved;

        return $this;
    }

    /**
     * Get areasserved.
     *
     * @return string
     */
    public function getAreasserved()
    {
        return $this->areasserved;
    }

    /**
     * Set locationdescription.
     *
     * @param string $locationdescription
     * @return Propertydetail
     */
    public function setLocationdescription($locationdescription)
    {
        $this->locationdescription = $locationdescription;

        return $this;
    }

    /**
     * Get locationdescription.
     *
     * @return string
     */
    public function getLocationdescription()
    {
        return $this->locationdescription;
    }

    /**
     * Set checkin.
     *
     * @param string $checkin
     * @return Propertydetail
     */
    public function setCheckin($checkin)
    {
        $this->checkin = $checkin;

        return $this;
    }

    /**
     * Get checkin.
     *
     * @return string
     */
    public function getCheckin()
    {
        return $this->checkin;
    }

    /**
     * Set checkout.
     *
     * @param string $checkout
     * @return Propertydetail
     */
    public function setCheckout($checkout)
    {
        $this->checkout = $checkout;

        return $this;
    }

    /**
     * Get checkout.
     *
     * @return string
     */
    public function getCheckout()
    {
        return $this->checkout;
    }

    /**
     * Set numberoffloors.
     *
     * @param int $numberoffloors
     * @return Propertydetail
     */
    public function setNumberoffloors($numberoffloors)
    {
        $this->numberoffloors = $numberoffloors;

        return $this;
    }

    /**
     * Get numberoffloors.
     *
     * @return int
     */
    public function getNumberoffloors()
    {
        return $this->numberoffloors;
    }

    /**
     * Set numberofrooms.
     *
     * @param int $numberofrooms
     * @return Propertydetail
     */
    public function setNumberofrooms($numberofrooms)
    {
        $this->numberofrooms = $numberofrooms;

        return $this;
    }

    /**
     * Get numberofrooms.
     *
     * @return int
     */
    public function getNumberofrooms()
    {
        return $this->numberofrooms;
    }

    /**
     * Set numberofsuites.
     *
     * @param int $numberofsuites
     * @return Propertydetail
     */
    public function setNumberofsuites($numberofsuites)
    {
        $this->numberofsuites = $numberofsuites;

        return $this;
    }

    /**
     * Get numberofsuites.
     *
     * @return int
     */
    public function getNumberofsuites()
    {
        return $this->numberofsuites;
    }

    /**
     * Set phonenumber.
     *
     * @param string $phonenumber
     * @return Propertydetail
     */
    public function setPhonenumber($phonenumber)
    {
        $this->phonenumber = $phonenumber;

        return $this;
    }

    /**
     * Get phonenumber.
     *
     * @return string
     */
    public function getPhonenumber()
    {
        return $this->phonenumber;
    }

    /**
     * Set faxnumber.
     *
     * @param string $faxnumber
     * @return Propertydetail
     */
    public function setFaxnumber($faxnumber)
    {
        $this->faxnumber = $faxnumber;

        return $this;
    }

    /**
     * Get faxnumber.
     *
     * @return string
     */
    public function getFaxnumber()
    {
        return $this->faxnumber;
    }

    /**
     * Set latitude.
     *
     * @param float $latitude
     * @return Propertydetail
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude.
     *
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude.
     *
     * @param float $longitude
     * @return Propertydetail
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude.
     *
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set address1.
     *
     * @param string $address1
     * @return Propertydetail
     */
    public function setAddress1($address1)
    {
        $this->address1 = $address1;

        return $this;
    }

    /**
     * Get address1.
     *
     * @return string
     */
    public function getAddress1()
    {
        return $this->address1;
    }

    /**
     * Set address2.
     *
     * @param string $address2
     * @return Propertydetail
     */
    public function setAddress2($address2)
    {
        $this->address2 = $address2;

        return $this;
    }

    /**
     * Get address2.
     *
     * @return string
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * Set cityname.
     *
     * @param string $cityname
     * @return Propertydetail
     */
    public function setCityname($cityname)
    {
        $this->cityname = $cityname;

        return $this;
    }

    /**
     * Get cityname.
     *
     * @return string
     */
    public function getCityname()
    {
        return $this->cityname;
    }

    /**
     * Set statecode.
     *
     * @param string $statecode
     * @return Propertydetail
     */
    public function setStatecode($statecode)
    {
        $this->statecode = $statecode;

        return $this;
    }

    /**
     * Get statecode.
     *
     * @return string
     */
    public function getStatecode()
    {
        return $this->statecode;
    }

    /**
     * Set zip.
     *
     * @param string $zip
     * @return Propertydetail
     */
    public function setZip($zip)
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * Get zip.
     *
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Set countrycode.
     *
     * @param string $countrycode
     * @return Propertydetail
     */
    public function setCountrycode($countrycode)
    {
        $this->countrycode = $countrycode;

        return $this;
    }

    /**
     * Get countrycode.
     *
     * @return string
     */
    public function getCountrycode()
    {
        return $this->countrycode;
    }
}

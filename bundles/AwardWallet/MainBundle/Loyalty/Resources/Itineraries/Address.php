<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class Address.
 *
 * @property $text
 * @property $addressLine
 * @property $city
 * @property $stateName
 * @property $countryName
 * @property $postalCode
 * @property $lat
 * @property $lng
 * @property $airportCode
 * @property $timezone
 */
class Address extends LoggerEntity
{
    /**
     * @var string
     * @Type("string")
     */
    protected $text;

    /**
     * @var string
     * @Type("string")
     */
    protected $addressLine;

    /**
     * @var string
     * @Type("string")
     */
    protected $city;

    /**
     * @var string
     * @Type("string")
     */
    protected $stateName;

    /**
     * @var string
     * @Type("string")
     */
    protected $countryName;

    /**
     * @var string
     * @Type("string")
     */
    protected $postalCode;

    /**
     * @var string
     * @Type("string")
     */
    protected $lat;

    /**
     * @var string
     * @Type("string")
     */
    protected $lng;

    /**
     * @var string
     * @Type("string")
     */
    protected $airportCode;

    /**
     * @var string
     * @Type("string")
     */
    protected $timezone;
}

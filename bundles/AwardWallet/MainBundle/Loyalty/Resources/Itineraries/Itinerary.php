<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class Itinerary.
 *
 * @property $providerDetails
 * @property $totalPrice
 * @property $type
 */
abstract class Itinerary extends LoggerEntity
{
    public const ISO_DATE_FORMAT = 'Y-m-d\TH:i:s';

    public const ITINERARIES_KINDS = [
        "T" => "Trip",
        "L" => "Rental",
        "R" => "Reservation",
        "D" => "Direction",
        "E" => "Restaurant",
        "P" => "Parking",
    ];

    /**
     * @var ProviderDetails
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\Itineraries\ProviderDetails")
     */
    protected $providerDetails;

    /**
     * @var TotalPrice
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\Itineraries\TotalPrice")
     */
    protected $totalPrice;

    /**
     * @var string
     * @Type("string")
     */
    protected $type;

    public function __construct()
    {
        $this->type = lcfirst(preg_replace('/^.+\\\/ims', '', get_class($this)));
    }
}

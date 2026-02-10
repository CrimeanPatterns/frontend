<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class TotalPrice.
 *
 * @property float $total
 * @property $cost
 * @property $spentAwards
 * @property $currencyCode
 * @property $tax
 * @property $fees
 * @property $rate
 * @property $rateType
 */
class TotalPrice extends LoggerEntity
{
    /**
     * @var float
     * @Type("double")
     */
    protected $total;

    /**
     * @var float
     * @Type("double")
     */
    protected $cost;

    /**
     * @var string
     * @Type("string")
     */
    protected $spentAwards;

    /**
     * @var string
     * @Type("string")
     */
    protected $currencyCode;

    /**
     * @var float
     * @Type("double")
     */
    protected $tax;
    /**
     * @var float
     * @Type("double")
     */
    protected $discount;
    /**
     * @var string
     * @Type("string")
     */
    protected $rate;
    /**
     * @var string
     * @Type("string")
     */
    protected $rateType;
    /**
     * @var Fee[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\Itineraries\Fee>")
     */
    protected $fees;
}

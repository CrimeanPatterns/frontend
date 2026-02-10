<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability;

use JMS\Serializer\Annotation\Type;

class CalendarItem
{
    /**
     * @Type("integer")
     * @var int
     */
    private $miles;

    /**
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability\Payments")
     * @var Payments
     */
    private $cashCost;

    /**
     * @Type("string")
     * @var string
     */
    private $standardItineraryCOS;

    /**
     * @Type("string")
     * @var string
     */
    private $brandedItineraryCOS;

    /**
     * @Type("string")
     * @var string
     */
    private $date;

    public function __construct(?int $miles, ?Payments $cashCost, ?string $standardItineraryCOS, ?string $brandedItineraryCOS, string $date)
    {
        $this->miles = $miles;
        $this->cashCost = $cashCost;
        $this->standardItineraryCOS = $standardItineraryCOS;
        $this->brandedItineraryCOS = $brandedItineraryCOS;
        $this->date = $date;
    }

    public function getMiles(): ?int
    {
        return $this->miles;
    }

    public function getCashCost(): ?Payments
    {
        return $this->cashCost;
    }

    public function getStandardItineraryCOS(): ?string
    {
        return $this->standardItineraryCOS;
    }

    public function getBrandedItineraryCOS(): ?string
    {
        return $this->brandedItineraryCOS;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }
}

<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability;

use JMS\Serializer\Annotation\Type;

class Times
{
    /**
     * @Type("string")
     * @var string
     */
    private $flight;
    /**
     * @Type("string")
     * @var string
     */
    private $layover;

    public function __construct(?string $flight, ?string $layover)
    {
        $this->flight = $flight;
        $this->layover = $layover;
    }

    public function getFlight(): ?int
    {
        if (!$this->flight) {
            return null;
        }
        [$h, $m] = explode(':', $this->flight);

        return $h * 60 + $m;
    }
}

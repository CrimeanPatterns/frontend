<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability;

use JMS\Serializer\Annotation\Type;

class Redemptions
{
    /**
     * @Type("string")
     * @var string
     */
    private $program;
    /**
     * @Type("integer")
     * @var int
     */
    private $miles;

    public function __construct(?string $program, ?float $miles)
    {
        $this->program = $program;
        $this->miles = $miles;
    }

    /**
     * @return int
     */
    public function getMiles()
    {
        return $this->miles;
    }
}

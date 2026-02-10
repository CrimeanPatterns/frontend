<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability;

use JMS\Serializer\Annotation\Type;

class SegmentPoint
{
    /**
     * @Type("DateTime<'Y-m-d\TH:i:sP'>")
     * @var \DateTime
     */
    private $dateTime;
    /**
     * @Type("string")
     * @var string
     */
    private $airport;
    /**
     * @Type("string")
     * @var string
     */
    private $terminal;

    public function __construct(?string $dateTime, ?string $airport, ?string $terminal)
    {
        if ($dateTime) {
            $this->dateTime = new \DateTime($dateTime);
        }
        $this->airport = $airport;
        $this->terminal = $terminal;
    }

    public function getAirport(): ?string
    {
        return $this->airport;
    }

    public function getDateTime(): ?\DateTime
    {
        return $this->dateTime;
    }
}

<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability;

use JMS\Serializer\Annotation\Type;

class RewardAvailabilityFlights
{
    /**
     * @var string
     * @Type("string")
     */
    private $requestId;

    /**
     * @var string
     * @Type("string")
     */
    private $provider;

    /**
     * @var int
     * @Type("int")
     */
    private $passengers;

    /** @var array
     * @Type("array")
     */
    private $flights;

    /**
     * @var array
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability\CalendarItem>")
     */
    private $calendar;

    /** @var array
     * @Type("array")
     */
    private $stats;

    /**
     * @Type("DateTime<'Y-m-d\TH:i:sP'>")
     * @var \DateTime
     */
    private $requestDate;

    /**
     * @var string
     * @Type("string")
     */
    private $depCode;

    /**
     * @var string
     * @Type("string")
     */
    private $arrCode;

    /**
     * @var string
     * @Type("string")
     */
    private $cabin;

    /**
     * @var string
     * @Type("string")
     */
    private $partner;

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getFlights(): array
    {
        return $this->flights;
    }

    public function getCalendar(): ?array
    {
        return $this->calendar;
    }

    public function getStats(): ?array
    {
        return $this->stats;
    }

    public function getRequestDate(): \DateTime
    {
        return $this->requestDate;
    }

    public function getPassengers(): ?int
    {
        return $this->passengers;
    }

    public function getDepCode(): ?string
    {
        return $this->depCode;
    }

    public function getArrCode(): ?string
    {
        return $this->arrCode;
    }

    public function getCabin(): string
    {
        return $this->cabin;
    }

    public function getPartner(): string
    {
        return $this->partner;
    }
}

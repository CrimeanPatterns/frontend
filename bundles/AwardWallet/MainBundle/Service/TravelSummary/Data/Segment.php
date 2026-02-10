<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Data;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class Segment implements \JsonSerializable
{
    /**
     * @var \DateTime
     */
    private $depDate;
    /**
     * @var string
     */
    private $depCode;
    /**
     * @var string
     */
    private $arrCode;
    /**
     * @var string
     */
    private $travelPlan;
    /**
     * @var int
     */
    private $tripSegmentId;

    public function __construct(
        \DateTime $depDate,
        string $depCode,
        string $arrCode,
        string $travelPlan,
        int $tripSegmentId
    ) {
        $this->depDate = $depDate;
        $this->depCode = $depCode;
        $this->arrCode = $arrCode;
        $this->travelPlan = $travelPlan;
        $this->tripSegmentId = $tripSegmentId;
    }

    public function getDepDate(): \DateTime
    {
        return $this->depDate;
    }

    public function getDepCode(): string
    {
        return $this->depCode;
    }

    public function getArrCode(): string
    {
        return $this->arrCode;
    }

    public function getTravelPlan(): string
    {
        return $this->travelPlan;
    }

    public function getTripSegmentId(): int
    {
        return $this->tripSegmentId;
    }

    public function jsonSerialize()
    {
        return [
            'depDate' => $this->depDate,
            'depCode' => $this->depCode,
            'arrCode' => $this->arrCode,
            'travelPlan' => $this->travelPlan,
            'tripSegmentId' => $this->tripSegmentId,
        ];
    }
}

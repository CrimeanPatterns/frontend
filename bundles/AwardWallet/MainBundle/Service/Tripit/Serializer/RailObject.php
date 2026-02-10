<?php

namespace AwardWallet\MainBundle\Service\Tripit\Serializer;

use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;

class RailObject extends BaseSerializer
{
    /**
     * @var string
     * @Assert\NotBlank()
     * @Type("string")
     */
    protected $booking_site_conf_num;
    /**
     * @var string
     * @Type("string")
     */
    private $total_cost;
    /**
     * @var RailSegmentObject[]
     * @Assert\NotBlank()
     * @Assert\Valid()
     * @Assert\All(
     *     @Assert\Type(RailSegmentObject::class)
     * )
     * @Type("array<AwardWallet\MainBundle\Service\Tripit\Serializer\RailSegmentObject>")
     */
    private $Segment;
    /**
     * @var TravelerObject[]
     * @Type("array<AwardWallet\MainBundle\Service\Tripit\Serializer\TravelerObject>")
     */
    private $Traveler = [];

    public function getTotalCost()
    {
        return $this->total_cost;
    }

    public function getSegment(): array
    {
        return $this->Segment;
    }

    public function getTraveler(): array
    {
        return $this->Traveler;
    }
}

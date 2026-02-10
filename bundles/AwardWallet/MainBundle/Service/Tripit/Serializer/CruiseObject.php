<?php

namespace AwardWallet\MainBundle\Service\Tripit\Serializer;

use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;

class CruiseObject extends BaseSerializer
{
    /**
     * @var string
     * @Assert\NotBlank()
     * @Type("string")
     */
    protected $booking_site_conf_num;
    /**
     * @var CruiseSegmentObject[]
     * @Assert\NotBlank()
     * @Assert\Valid()
     * @Assert\All(
     *     @Assert\Type(CruiseSegmentObject::class)
     * )
     * @Type("array<AwardWallet\MainBundle\Service\Tripit\Serializer\CruiseSegmentObject>")
     */
    private $Segment;
    /**
     * @var TravelerObject[]
     * @Type("array<AwardWallet\MainBundle\Service\Tripit\Serializer\TravelerObject>")
     */
    private $Traveler = [];
    /**
     * @var string
     * @Type("string")
     */
    private $cabin_number;
    /**
     * @var string
     * @Type("string")
     */
    private $cabin_type;
    /**
     * @var string
     * @Type("string")
     */
    private $ship_name;

    public function getSegment(): array
    {
        return $this->Segment;
    }

    public function getTraveler(): array
    {
        return $this->Traveler;
    }

    public function getCabinNumber()
    {
        return $this->cabin_number;
    }

    public function getCabinType()
    {
        return $this->cabin_type;
    }

    public function getShipName()
    {
        return $this->ship_name;
    }
}

<?php

namespace AwardWallet\MainBundle\Service\Tripit\Serializer;

use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;

class AirObject extends BaseSerializer
{
    /**
     * @var string
     * @Assert\NotBlank()
     * @Type("string")
     */
    protected $supplier_conf_num;
    /**
     * @var AirSegmentObject[]
     * @Assert\NotBlank()
     * @Assert\Valid()
     * @Assert\All(
     *     @Assert\Type(AirSegmentObject::class),
     * )
     * @Type("array<AwardWallet\MainBundle\Service\Tripit\Serializer\AirSegmentObject>")
     */
    private $Segment;
    /**
     * @var TravelerObject[]
     * @Assert\All(
     *     @Assert\Type(TravelerObject::class)
     * )
     * @Type("array<AwardWallet\MainBundle\Service\Tripit\Serializer\TravelerObject>")
     */
    private $Traveler = [];

    public function getSegment(): array
    {
        return $this->Segment;
    }

    public function getTraveler(): array
    {
        return $this->Traveler;
    }
}

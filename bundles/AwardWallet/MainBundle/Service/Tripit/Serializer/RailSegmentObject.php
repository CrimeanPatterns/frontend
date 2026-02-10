<?php

namespace AwardWallet\MainBundle\Service\Tripit\Serializer;

use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;

class RailSegmentObject
{
    /**
     * @var DateTimeObject
     * @Assert\NotBlank()
     * @Assert\Valid()
     * @Assert\Type(DateTimeObject::class)
     * @Type("AwardWallet\MainBundle\Service\Tripit\Serializer\DateTimeObject")
     */
    private $StartDateTime;
    /**
     * @var DateTimeObject
     * @Assert\NotBlank()
     * @Assert\Valid()
     * @Assert\Type(DateTimeObject::class)
     * @Type("AwardWallet\MainBundle\Service\Tripit\Serializer\DateTimeObject")
     */
    private $EndDateTime;
    /**
     * @var AddressObject
     * @Assert\NotBlank()
     * @Assert\Valid()
     * @Assert\Type(AddressObject::class)
     * @Type("AwardWallet\MainBundle\Service\Tripit\Serializer\AddressObject")
     */
    private $StartStationAddress;
    /**
     * @var AddressObject
     * @Assert\NotBlank()
     * @Assert\Valid()
     * @Assert\Type(AddressObject::class)
     * @Type("AwardWallet\MainBundle\Service\Tripit\Serializer\AddressObject")
     */
    private $EndStationAddress;
    /**
     * @var string
     * @Type("string")
     */
    private $carrier_name;
    /**
     * @var string
     * @Type("string")
     */
    private $confirmation_num;
    /**
     * @var string
     * @Type("string")
     */
    private $seats;
    /**
     * @var string
     * @Type("string")
     */
    private $service_class;

    public function getStartDateTime(): DateTimeObject
    {
        return $this->StartDateTime;
    }

    public function getEndDateTime(): DateTimeObject
    {
        return $this->EndDateTime;
    }

    public function getStartStationAddress(): AddressObject
    {
        return $this->StartStationAddress;
    }

    public function getEndStationAddress(): AddressObject
    {
        return $this->EndStationAddress;
    }

    public function getCarrierName()
    {
        return $this->carrier_name;
    }

    public function getConfirmationNum()
    {
        return $this->confirmation_num;
    }

    public function getSeats()
    {
        return $this->seats;
    }

    public function getServiceClass()
    {
        return $this->service_class;
    }
}

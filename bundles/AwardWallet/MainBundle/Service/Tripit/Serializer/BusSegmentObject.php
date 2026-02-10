<?php

namespace AwardWallet\MainBundle\Service\Tripit\Serializer;

use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;

class BusSegmentObject
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
    private $StartLocationAddress;
    /**
     * @var AddressObject
     * @Assert\NotBlank()
     * @Assert\Valid()
     * @Assert\Type(AddressObject::class)
     * @Type("AwardWallet\MainBundle\Service\Tripit\Serializer\AddressObject")
     */
    private $EndLocationAddress;
    /**
     * @var string
     * @Assert\NotBlank()
     * @Type("string")
     */
    private $start_location_name;
    /**
     * @var string
     * @Assert\NotBlank()
     * @Type("string")
     */
    private $end_location_name;
    /**
     * @var string
     * @Type("string")
     */
    private $detail_type_code;
    /**
     * @var string
     * @Type("string")
     */
    private $carrier_name;
    /**
     * @var string
     * @Type("string")
     */
    private $vehicle_description;

    public function getStartDateTime(): DateTimeObject
    {
        return $this->StartDateTime;
    }

    public function getEndDateTime(): DateTimeObject
    {
        return $this->EndDateTime;
    }

    public function getStartLocationAddress(): AddressObject
    {
        return $this->StartLocationAddress;
    }

    public function getEndLocationAddress(): AddressObject
    {
        return $this->EndLocationAddress;
    }

    public function getStartLocationName()
    {
        return $this->start_location_name;
    }

    public function getEndLocationName()
    {
        return $this->end_location_name;
    }

    public function getDetailTypeCode()
    {
        return $this->detail_type_code;
    }

    public function getCarrierName()
    {
        return $this->carrier_name;
    }

    public function getVehicleDescription()
    {
        return $this->vehicle_description;
    }
}

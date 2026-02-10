<?php

namespace AwardWallet\MainBundle\Service\Tripit\Serializer;

use AwardWallet\MainBundle\Service\Tripit\Serializer\GuestObject as DriverObject;
use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;

class CarObject extends BaseSerializer
{
    /**
     * @var string
     * @Assert\NotBlank()
     * @Type("string")
     */
    protected $supplier_conf_num;
    /**
     * @var string
     * @Type("string")
     */
    private $restrictions;
    /**
     * @var string
     * @Type("string")
     */
    private $total_cost;
    /**
     * @var DateTimeObject
     * @Assert\NotBlank()
     * @Assert\Type(DateTimeObject::class)
     * @Type("AwardWallet\MainBundle\Service\Tripit\Serializer\DateTimeObject")
     */
    private $StartDateTime;
    /**
     * @var DateTimeObject
     * @Assert\NotBlank()
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
     * @var DriverObject
     * @Type("AwardWallet\MainBundle\Service\Tripit\Serializer\GuestObject")
     */
    private $Driver;
    /**
     * @var string
     * @Type("string")
     */
    private $start_location_hours;
    /**
     * @var string
     * @Type("string")
     */
    private $start_location_name;
    /**
     * @var string
     * @Type("string")
     */
    private $start_location_phone;
    /**
     * @var string
     * @Type("string")
     */
    private $end_location_hours;
    /**
     * @var string
     * @Type("string")
     */
    private $end_location_name;
    /**
     * @var string
     * @Type("string")
     */
    private $end_location_phone;
    /**
     * @var string
     * @Type("string")
     */
    private $car_description;
    /**
     * @var string
     * @Type("string")
     */
    private $car_type;

    public function getRestrictions()
    {
        return $this->restrictions;
    }

    public function getTotalCost()
    {
        return $this->total_cost;
    }

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

    public function getDriver(): ?DriverObject
    {
        return $this->Driver;
    }

    public function getStartLocationHours()
    {
        return $this->start_location_hours;
    }

    public function getStartLocationName()
    {
        return $this->start_location_name;
    }

    public function getStartLocationPhone()
    {
        return $this->start_location_phone;
    }

    public function getEndLocationHours()
    {
        return $this->end_location_hours;
    }

    public function getEndLocationName()
    {
        return $this->end_location_name;
    }

    public function getEndLocationPhone()
    {
        return $this->end_location_phone;
    }

    public function getCarDescription()
    {
        return $this->car_description;
    }

    public function getCarType()
    {
        return $this->car_type;
    }
}

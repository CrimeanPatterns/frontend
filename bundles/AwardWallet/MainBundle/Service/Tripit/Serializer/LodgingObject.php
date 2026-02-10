<?php

namespace AwardWallet\MainBundle\Service\Tripit\Serializer;

use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;

class LodgingObject extends BaseSerializer
{
    /**
     * @var string
     * @Assert\NotBlank()
     * @Type("string")
     */
    protected $supplier_conf_num;
    /**
     * @var string
     * @Assert\NotBlank()
     * @Type("string")
     */
    protected $supplier_name;
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
    private $Address;
    /**
     * @var GuestObject[]
     * @Type("array<AwardWallet\MainBundle\Service\Tripit\Serializer\GuestObject>")
     */
    private $Guest = [];
    /**
     * @var int
     * @Type("int")
     */
    private $number_guests;
    /**
     * @var int
     * @Type("int")
     */
    private $number_rooms;
    /**
     * @var string
     * @Type("string")
     */
    private $room_type;

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

    public function getAddress(): AddressObject
    {
        return $this->Address;
    }

    public function getGuest(): array
    {
        return $this->Guest;
    }

    public function getNumberGuests()
    {
        return $this->number_guests;
    }

    public function getNumberRooms()
    {
        return $this->number_rooms;
    }

    public function getRoomType()
    {
        return $this->room_type;
    }
}

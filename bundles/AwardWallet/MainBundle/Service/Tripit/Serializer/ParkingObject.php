<?php

namespace AwardWallet\MainBundle\Service\Tripit\Serializer;

use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ParkingObject extends BaseSerializer
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
     * @var string
     * @Type("string")
     */
    private $location_name;
    /**
     * @var string
     * @Type("string")
     */
    private $location_phone;

    /**
     * @Assert\Callback()
     */
    public function validateEmptyDate(ExecutionContextInterface $context, $payload)
    {
        // В некоторых случаях в объекте `EndDateTime`, отсутствует свойство `date`. Принимаем, что эта дата
        // соответствует `StartDateTime`, но проверяем, чтобы время не было больше.

        if ($this->getEndDateTime() !== null
            && $this->getEndDateTime()->getDate() === null
            && strtotime($this->getStartDateTime()->getTime()) > strtotime($this->getEndDateTime()->getTime())
        ) {
            $context->buildViolation('Date cannot be blank.')
                ->atPath('EndDateTime.date')
                ->addViolation();
        }
    }

    public function getTotalCost()
    {
        return $this->total_cost;
    }

    public function getStartDateTime(): DateTimeObject
    {
        return $this->StartDateTime;
    }

    public function getEndDateTime(): ?DateTimeObject
    {
        return $this->EndDateTime;
    }

    public function getAddress(): AddressObject
    {
        return $this->Address;
    }

    public function getLocationName()
    {
        return $this->location_name;
    }

    public function getLocationPhone()
    {
        return $this->location_phone;
    }
}

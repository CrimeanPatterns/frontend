<?php

namespace AwardWallet\MainBundle\Service\Tripit\Serializer;

use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CruiseSegmentObject
{
    /**
     * @var DateTimeObject
     * @Assert\NotBlank()
     * @Assert\Type(DateTimeObject::class)
     * @Type("AwardWallet\MainBundle\Service\Tripit\Serializer\DateTimeObject")
     */
    private $StartDateTime;
    /**
     * @var DateTimeObject|null
     * @Assert\Type(DateTimeObject::class)
     * @Type("AwardWallet\MainBundle\Service\Tripit\Serializer\DateTimeObject")
     */
    private $EndDateTime;
    /**
     * @var AddressObject
     * @Assert\NotBlank()
     * @Assert\Type(AddressObject::class)
     * @Type("AwardWallet\MainBundle\Service\Tripit\Serializer\AddressObject")
     */
    private $LocationAddress;
    /**
     * @var string
     * @Assert\NotBlank()
     * @Type("string")
     */
    private $location_name;
    /**
     * @var string|null
     * @Assert\Type(type="string")
     * @Type("string")
     */
    private $detail_type_code;

    /**
     * @Assert\Callback()
     */
    public function validateEndDateTime(ExecutionContextInterface $context, $payload)
    {
        // У первого и последнего сегментов круиза отсутствует свойство `EndDateTime`, но для конвертации в объект
        // `Itineraries\Cruise` они не требуются. Во всех остальных сегментах это свойство обязательно.
        // В TripIt сегменты круиза называются "Port Of Call" и имеют дополнительное свойство `detail_type_code`.
        // Но в первом и последнем сегментах это свойство отсутствует.

        if ($this->getDetailTypeCode() !== null && $this->getEndDateTime() === null) {
            $context->buildViolation('End datetime cannot be blank.')
                ->atPath('EndDateTime')
                ->addViolation();
        }
    }

    /**
     * @Assert\Callback()
     */
    public function validateEmptyDate(ExecutionContextInterface $context, $payload)
    {
        // В некоторых случаях в объекте `EndDateTime`, отсутствует свойство `date`. Принимаем, что эта дата
        // соответствует `StartDateTime`, но проверяем, чтобы время не было больше.

        if ($this->getDetailTypeCode() !== null
            && $this->getEndDateTime() !== null
            && $this->getEndDateTime()->getDate() === null
            && strtotime($this->getStartDateTime()->getTime()) > strtotime($this->getEndDateTime()->getTime())
        ) {
            $context->buildViolation('Date cannot be blank.')
                ->atPath('EndDateTime.date')
                ->addViolation();
        }
    }

    /**
     * @Assert\Callback()
     */
    public function validateAddressValue(ExecutionContextInterface $context, $payload)
    {
        if ($this->getLocationAddress() !== null) {
            // Дополнительная проверка поля `address` для случая, когда в нём прописаны географические координаты,
            // а не реальный почтовый адрес.

            $address = $this->getLocationAddress()->getAddress();

            if ($address === ''
                || ($this->getLocationAddress()->getCountry() === ''
                && $this->getLocationAddress()->getLatitude() === ''
                && $this->getLocationAddress()->getLongitude() === ''
                && !preg_match('/^[-+]?\d{1,2}\.\d+\,\s*[-+]?\d{1,3}\.\d+$/', $address))
            ) {
                $context->buildViolation('Address is not valid.')
                    ->atPath('LocationAddress.address')
                    ->addViolation();
            }
        }
    }

    public function getStartDateTime(): DateTimeObject
    {
        return $this->StartDateTime;
    }

    public function getEndDateTime(): ?DateTimeObject
    {
        return $this->EndDateTime;
    }

    public function setEndDateTime(DateTimeObject $date)
    {
        $this->EndDateTime = $date;
    }

    public function getLocationAddress(): ?AddressObject
    {
        return $this->LocationAddress;
    }

    public function getLocationName()
    {
        return $this->location_name;
    }

    public function getDetailTypeCode()
    {
        return $this->detail_type_code;
    }
}

<?php

namespace AwardWallet\MainBundle\Service\Tripit\Serializer;

use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class FerryObject extends BaseSerializer
{
    /**
     * @var string
     * @Type("string")
     */
    private $total_cost;
    /**
     * @var FerrySegmentObject[]
     * @Assert\NotBlank()
     * @Assert\Valid()
     * @Assert\All(
     *     @Assert\Type(FerrySegmentObject::class)
     * )
     * @Type("array<AwardWallet\MainBundle\Service\Tripit\Serializer\FerrySegmentObject>")
     */
    private $Segment;
    /**
     * @var TravelerObject[]
     * @Type("array<AwardWallet\MainBundle\Service\Tripit\Serializer\TravelerObject>")
     */
    private $Traveler = [];

    /**
     * @Assert\Callback()
     */
    public function validateConfirmationNumber(ExecutionContextInterface $context, $payload)
    {
        if ($this->getBookingSiteConfNum() === null && $this->getSupplierConfNum() === null) {
            $context->buildViolation('Confirmation number cannot be blank.')
                ->atPath('supplier_conf_num')
                ->addViolation();
        }
    }

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

    /**
     * Проверяем наличие "Confirmation number" в обоих свойствах объекта: `supplier_conf_num` и `booking_site_conf_num`,
     * и возвращаем значение того свойства, которое не пустое.
     */
    public function getConfirmationNumber(): ?string
    {
        if (
            ($this->getSupplierConfNum() !== null && $this->getBookingSiteConfNum() !== null)
            || ($this->getSupplierConfNum() !== null && $this->getBookingSiteConfNum() === null)
        ) {
            return $this->getSupplierConfNum();
        } elseif ($this->getSupplierConfNum() === null && $this->getBookingSiteConfNum() !== null) {
            return $this->getBookingSiteConfNum();
        }

        return null;
    }
}

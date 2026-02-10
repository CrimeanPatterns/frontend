<?php

namespace AwardWallet\MainBundle\Form\Model\Booking;

use AwardWallet\MobileBundle\Form\Model\AbstractEntityAwareModel;

class InvoiceItemModel extends AbstractEntityAwareModel
{
    /**
     * @var string
     */
    private $description;

    /**
     * @var int
     */
    private $quantity = 1;

    /**
     * @var float
     */
    private $price = 0;

    /**
     * @var int
     */
    private $discount = 0;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): InvoiceItemModel
    {
        $this->description = $description;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): InvoiceItemModel
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): InvoiceItemModel
    {
        $this->price = $price;

        return $this;
    }

    public function getDiscount(): ?int
    {
        return $this->discount;
    }

    public function setDiscount(int $discount): InvoiceItemModel
    {
        $this->discount = $discount;

        return $this;
    }
}

<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AwardWallet\MainBundle\Entity\AbInvoiceItem.
 *
 * @ORM\Entity()
 * @ORM\Table(name="AbInvoiceItem")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="Type", type="integer")
 * @ORM\DiscriminatorMap({
 *  "0" = "AwardWallet\MainBundle\Entity\BookingInvoiceItem\BookingServiceFee",
 *  "1" = "AwardWallet\MainBundle\Entity\BookingInvoiceItem\Tax",
 *  "2" = "AwardWallet\MainBundle\Entity\BookingInvoiceItem\CreditCardFee",
 *  "100" = "AwardWallet\MainBundle\Entity\BookingInvoiceItem\Item"
 * })
 */
abstract class AbInvoiceItem
{
    /**
     * @var int
     * @ORM\Column(name="AbInvoiceItemID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="Description", type="string", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Type(type = "string")
     * @Assert\Length(min = "3", max = "255", allowEmptyString="true")
     */
    protected $description;

    /**
     * @var int
     * @ORM\Column(name="Quantity", type="integer", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Range(min = "1", max = "10000")
     */
    protected $quantity = 1;

    /**
     * @var float
     * @ORM\Column(name="Price", type="decimal", length=10, nullable=false, scale=2)
     * @Assert\NotBlank()
     * @Assert\Range(min = "-1000000", max = "1000000")
     */
    protected $price = 0;

    /**
     * @var int
     * @ORM\Column(name="Discount", type="integer", nullable=true)
     * @Assert\Range(min = "0", max = "100")
     */
    protected $discount = 0;

    /**
     * @var AbInvoice
     * @ORM\ManyToOne(targetEntity="AbInvoice", inversedBy="items")
     * @ORM\JoinColumn(name="AbInvoiceID", referencedColumnName="AbInvoiceID", nullable=false)
     */
    protected $invoice;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return $this
     */
    public function setDescription(?string $description)
    {
        $this->description = $description;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @return $this
     */
    public function setQuantity(int $quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @return $this
     */
    public function setPrice(float $price)
    {
        $this->price = $price;

        return $this;
    }

    public function getDiscount(): ?float
    {
        return $this->discount;
    }

    /**
     * @return $this
     */
    public function setDiscount(float $discount)
    {
        $this->discount = $discount;

        return $this;
    }

    public function getInvoice(): AbInvoice
    {
        return $this->invoice;
    }

    public function setInvoice(AbInvoice $invoice)
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getTotal(): float
    {
        return $this->getPriceTotal() - $this->getDiscountAmount();
    }

    public function getPriceTotal(): float
    {
        return $this->getPrice() * $this->getQuantity();
    }

    public function getDiscountAmount(): float
    {
        return round($this->getPriceTotal() * ($this->getDiscount() / 100), 2);
    }
}

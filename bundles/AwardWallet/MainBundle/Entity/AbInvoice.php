<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Entity\BookingInvoiceItem\BookingServiceFee;
use AwardWallet\MainBundle\Entity\BookingInvoiceItem\CreditCardFee;
use AwardWallet\MainBundle\Entity\BookingInvoiceItem\Tax;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AwardWallet\MainBundle\Entity\AbInvoice.
 *
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\AbInvoiceRepository")
 * @ORM\Table(name="AbInvoice", indexes={@ORM\Index(name="IDX_7F9A558A1D5C02B1", columns={"MessageID"})})
 */
class AbInvoice
{
    public const STATUS_UNPAID = 0;
    public const STATUS_PAID = 1;

    public const PAYMENTTYPE_CREDITCARD = 0;
    public const PAYMENTTYPE_CHECK = 1;

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="AbInvoiceID", type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     * @ORM\Column(name="Status", type="integer", length=1, nullable=false)
     */
    protected $status = 0;

    /**
     * @var int
     * @ORM\Column(name="PaymentType", type="integer", length=1, nullable=false)
     */
    protected $paymentType = 0;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumn(name="PaidTo", referencedColumnName="UserID", nullable=true)
     */
    protected $paidTo;

    /**
     * @var AbTransaction
     * @ORM\ManyToOne(targetEntity="AbTransaction", inversedBy="invoices")
     * @ORM\JoinColumn(
     *     name="TransactionID",
     *     referencedColumnName="AbTransactionID",
     *     nullable=true
     * )
     */
    protected $transaction;

    /**
     * @var AbInvoiceMiles[]
     * @ORM\OneToMany(targetEntity="AbInvoiceMiles", mappedBy="InvoiceID", cascade={"persist", "remove"})
     */
    protected $miles;

    /**
     * @var AbInvoiceItem[]
     * @Assert\Count(min = "1", max = "20")
     * @ORM\OneToMany(targetEntity="AbInvoiceItem", mappedBy="invoice", cascade={"persist", "remove"})
     */
    protected $items;

    /**
     * @var AbMessage
     * @ORM\OneToOne(targetEntity="AbMessage", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="MessageID", referencedColumnName="AbMessageID", nullable=false)
     */
    protected $message;

    public function __construct()
    {
        $this->miles = new ArrayCollection();
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setStatus(int $status): AbInvoice
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setPaymentType(int $paymentType): AbInvoice
    {
        $this->paymentType = $paymentType;

        return $this;
    }

    public function getPaymentType(): int
    {
        return $this->paymentType;
    }

    public function getPaidTo(): ?Usr
    {
        return $this->paidTo;
    }

    public function setPaidTo(?Usr $paidTo): AbInvoice
    {
        $this->paidTo = $paidTo;

        return $this;
    }

    public function getTransaction(): ?AbTransaction
    {
        return $this->transaction;
    }

    public function setTransaction(?AbTransaction $transaction): AbInvoice
    {
        $this->transaction = $transaction;

        return $this;
    }

    public function addMile(AbInvoiceMiles $miles): AbInvoice
    {
        $miles->setInvoice($this);
        $this->miles[] = $miles;

        return $this;
    }

    public function removeMile(AbInvoiceMiles $miles)
    {
        $this->miles->removeElement($miles);
    }

    /**
     * @return AbInvoiceMiles[]|null
     */
    public function getMiles()
    {
        return $this->miles;
    }

    public function addItem(AbInvoiceItem $item): AbInvoice
    {
        $item->setInvoice($this);
        $this->items[] = $item;

        return $this;
    }

    public function removeItem(AbInvoiceItem $item)
    {
        $this->items->removeElement($item);
    }

    /**
     * @return AbInvoiceItem[]|null
     */
    public function getItems()
    {
        return $this->items;
    }

    public function setMessage(AbMessage $message): AbInvoice
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage(): ?AbMessage
    {
        return $this->message;
    }

    public function getTotal(): float
    {
        $total = 0;

        foreach ($this->getItems() as $item) {
            $total += $item->getTotal();
        }

        return $total > 0 ? $total : 0;
    }

    public function getTotalWithoutDiscount(): float
    {
        $total = 0;

        foreach ($this->getItems() as $item) {
            $total += $item->getPriceTotal();
        }

        return $total > 0 ? $total : 0;
    }

    public function isPaid(): bool
    {
        return $this->getStatus() === self::STATUS_PAID;
    }

    public function getTotalBookingServiceFees(): float
    {
        $total = 0;

        foreach ($this->getItems() as $item) {
            if ($item instanceof BookingServiceFee) {
                $total += $item->getTotal();
            }
        }

        return $total;
    }

    public function getTotalTaxes(): float
    {
        $total = 0;

        foreach ($this->getItems() as $item) {
            if ($item instanceof Tax) {
                $total += $item->getTotal();
            }
        }

        return $total;
    }

    public function getFees(): float
    {
        $total = 0;

        foreach ($this->getItems() as $item) {
            if (!($item instanceof CreditCardFee)) {
                $total += $item->getTotal();
            }
        }

        return $total;
    }
}

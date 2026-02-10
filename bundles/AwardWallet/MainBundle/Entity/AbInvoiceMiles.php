<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AwardWallet\MainBundle\Entity\AbInvoiceMiles.
 *
 * @ORM\Entity()
 * @ORM\Table(name="AbInvoiceMiles", indexes={@ORM\Index(name="IDX_EF8251F084E47E3A", columns={"InvoiceID"})})
 */
class AbInvoiceMiles
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $AbInvoiceMilesID;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3", max = "255", allowEmptyString="true")
     */
    protected $CustomName;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3", max = "255", allowEmptyString="true")
     */
    protected $Owner;

    /**
     * @var float
     * @ORM\Column(type="decimal", length=10, nullable=false)
     * @Assert\NotBlank()
     */
    protected $Balance;

    /**
     * @var AbInvoice
     * @ORM\ManyToOne(targetEntity="AbInvoice", inversedBy="miles")
     * @ORM\JoinColumn(name="InvoiceID", referencedColumnName="AbInvoiceID", nullable=false)
     */
    protected $InvoiceID;

    /**
     * Get AbInvoiceMilesID.
     *
     * @return int
     */
    public function getAbInvoiceMilesID()
    {
        return $this->AbInvoiceMilesID;
    }

    /**
     * Set CustomName.
     *
     * @param string $customName
     * @return AbInvoiceMiles
     */
    public function setCustomName($customName)
    {
        $this->CustomName = $customName;

        return $this;
    }

    /**
     * Get CustomName.
     *
     * @return string
     */
    public function getCustomName()
    {
        return $this->CustomName;
    }

    /**
     * @param string $Owner
     */
    public function setOwner($Owner)
    {
        $this->Owner = $Owner;
    }

    /**
     * @return string
     */
    public function getOwner()
    {
        return $this->Owner;
    }

    /**
     * Set Balance.
     *
     * @param float $balance
     * @return AbInvoiceMiles
     */
    public function setBalance($balance)
    {
        $this->Balance = $balance;

        return $this;
    }

    /**
     * Get Balance.
     *
     * @return float
     */
    public function getBalance()
    {
        return $this->Balance;
    }

    /**
     * Set invoice.
     *
     * @return AbInvoiceMiles
     */
    public function setInvoice(AbInvoice $invoice)
    {
        $this->InvoiceID = $invoice;

        return $this;
    }

    /**
     * Get invoice.
     *
     * @return \AwardWallet\MainBundle\Entity\AbInvoice
     */
    public function getInvoice()
    {
        return $this->InvoiceID;
    }
}

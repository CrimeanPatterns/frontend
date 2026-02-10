<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * AwardWallet\MainBundle\Entity\AbTransaction.
 *
 * @ORM\Entity
 * @ORM\Table(name="AbTransaction", indexes={@ORM\Index(name="Processed", columns={"Processed"})})
 */
class AbTransaction
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="AbTransactionID", type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     * @ORM\Column(name="ProcessDate", type="datetime", nullable=false)
     */
    protected $processDate;

    /**
     * @var int
     * @ORM\Column(name="Processed", type="integer", length=1, nullable=true)
     */
    protected $processed;

    /**
     * @var AbInvoice[]
     * @ORM\OneToMany(targetEntity="AbInvoice", mappedBy="transaction")
     */
    protected $invoices;

    public function __construct()
    {
        $this->invoices = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setProcessDate(\DateTime $processDate)
    {
        $this->processDate = $processDate;

        return $this;
    }

    public function getProcessDate(): ?\DateTime
    {
        return $this->processDate;
    }

    /**
     * Set Processed.
     *
     * @param int $processed
     * @return AbTransaction
     */
    public function setProcessed($processed)
    {
        $this->processed = $processed;

        return $this;
    }

    /**
     * Get Processed.
     *
     * @return int
     */
    public function getProcessed()
    {
        return $this->processed;
    }

    public function addInvoice(AbInvoice $invoice)
    {
        $this->invoices[] = $invoice;

        return $this;
    }

    public function removeInvoice(AbInvoice $invoice)
    {
        $this->invoices->removeElement($invoice);
    }

    public function getInvoices()
    {
        return $this->invoices;
    }
}

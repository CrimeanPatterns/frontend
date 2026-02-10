<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AwardWallet\MainBundle\Entity\BusinessTransaction.
 *
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\BusinessTransactionRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="Type", type="integer")
 * @ORM\DiscriminatorMap({
 *  "2" = "AwardWallet\MainBundle\Entity\BusinessTransaction\MembershipRenewed",
 *  "4" = "AwardWallet\MainBundle\Entity\BusinessTransaction\UpgradedToAwPlus",
 *  "5" = "AwardWallet\MainBundle\Entity\BusinessTransaction\AbRequestClosed",
 *  "6" = "AwardWallet\MainBundle\Entity\BusinessTransaction\Payment",
 *  "50" = "AwardWallet\MainBundle\Entity\BusinessTransaction\BalanceWatchStart",
 *  "51" = "AwardWallet\MainBundle\Entity\BusinessTransaction\BalanceWatchRefund"
 * })
 * @ORM\Table(
 *     name="BusinessTransaction",
 *     indexes={
 *        @ORM\Index(name="BusTrans_CreateDate", columns={"CreateDate"}),
 *        @ORM\Index(name="BusTrans_SourceID", columns={"SourceID"}),
 *        @ORM\Index(name="BusTrans_UserID_FK", columns={"UserID"}),
 *     }
 * )
 */
abstract class BusinessTransaction
{
    /**
     * @var int
     * @ORM\Column(name="BusinessTransactionID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreateDate", type="datetime", nullable=false)
     * @Assert\NotBlank()
     */
    protected $createDate;

    /**
     * @var float
     * @ORM\Column(name="Amount", type="decimal", length=12, scale=2, nullable=false)
     * @Assert\NotBlank()
     */
    protected $amount = 0;

    /**
     * @var float
     * @ORM\Column(name="Balance", type="decimal", length=12, scale=2, nullable=false)
     * @Assert\NotBlank()
     */
    protected $balance = 0;

    /**
     * @var int
     * @ORM\Column(name="SourceID", type="integer", nullable=true)
     */
    protected $sourceID;

    /**
     * @var string
     * @ORM\Column(name="SourceDesc", type="string", length=250, nullable=true)
     */
    protected $sourceDesc;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumn(name="UserID", referencedColumnName="UserID", nullable=false)
     */
    protected $user;

    public function __construct()
    {
        $this->createDate = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    /**
     * @return $this
     */
    public function setCreateDate(\DateTime $createDate)
    {
        $this->createDate = $createDate;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return float
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @param float $balance
     * @return $this
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * @return int
     */
    public function getSourceID()
    {
        return $this->sourceID;
    }

    /**
     * @param int $sourceID
     */
    public function setSourceID($sourceID)
    {
        $this->sourceID = $sourceID;

        return $this;
    }

    /**
     * @return string
     */
    public function getSourceDesc()
    {
        return $this->sourceDesc;
    }

    /**
     * @param string $sourceDesc
     * @return $this
     */
    public function setSourceDesc($sourceDesc)
    {
        $this->sourceDesc = $sourceDesc;

        return $this;
    }

    /**
     * @return Usr
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return $this
     */
    public function setUser(Usr $user)
    {
        $this->user = $user;

        return $this;
    }

    public function isFreeWhenTrial()
    {
        return false;
    }
}

<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="UserCreditCard")
 * @ORM\Entity
 */
class UserCreditCard
{
    public const SOURCE_PLACE_ACCOUNT = 1;
    public const SOURCE_PLACE_SUBACCOUNT = 2;
    public const SOURCE_PLACE_ACCOUNT_HISTORY = 3;
    public const SOURCE_PLACE_DETECTED_CARDS = 4;
    public const SOURCE_PLACE_QS_TRANSACTION = 5;
    public const SOURCE_PLACE_EMAIL = 6;

    /**
     * @var int
     * @ORM\Column(name="UserCreditCardID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $userCreditCardId;

    /**
     * @var bool
     * @ORM\Column(name="IsClosed", type="boolean", nullable=true)
     */
    private $isClosed;

    /**
     * @var \DateTime
     * @ORM\Column(name="EarliestSeenDate", type="datetime", nullable=true)
     */
    private $earliestSeenDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastSeenDate", type="datetime", nullable=true)
     */
    private $lastSeenDate;

    /**
     * @var bool
     * @ORM\Column(name="DetectedViaBank", type="boolean", nullable=true)
     */
    private $detectedViaBank;

    /**
     * @var bool
     * @ORM\Column(name="DetectedViaCobrand", type="boolean", nullable=true)
     */
    private $detectedViaCobrand;

    /**
     * @var bool
     * @ORM\Column(name="DetectedViaQS", type="boolean", nullable=true)
     */
    private $detectedViaQS;

    /**
     * @var \DateTime
     * @ORM\Column(name="ClosedDate", type="date", nullable=true)
     */
    private $closedDate;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="AwardWallet\MainBundle\Entity\Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    private $user;

    /**
     * @var CreditCard
     * @ORM\ManyToOne(targetEntity="AwardWallet\MainBundle\Entity\CreditCard")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="CreditCardID", referencedColumnName="CreditCardID")
     * })
     */
    private $creditCard;

    public function getId(): int
    {
        return $this->userCreditCardId;
    }

    public function isClosed(): ?bool
    {
        return $this->isClosed;
    }

    public function getEarliestSeenDate(): ?\DateTime
    {
        return $this->earliestSeenDate;
    }

    public function getLastSeenDate(): ?\DateTime
    {
        return $this->lastSeenDate;
    }

    public function getDetectedViaBank(): ?bool
    {
        return $this->detectedViaBank;
    }

    public function getDetectedViaCobrand(): ?bool
    {
        return $this->detectedViaCobrand;
    }

    public function getDetectedViaQS(): ?bool
    {
        return $this->detectedViaQS;
    }

    public function getClosedDate(): ?\DateTime
    {
        return $this->closedDate;
    }

    public function getUser(): ?Usr
    {
        return $this->user;
    }

    public function getCreditCard(): ?CreditCard
    {
        return $this->creditCard;
    }
}

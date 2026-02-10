<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="DetectedCard")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\DetectedCardRepository")
 */
class DetectedCard
{
    /**
     * @var Account
     * @ORM\ManyToOne(targetEntity="Account", inversedBy="detectedcard", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID")
     * })
     */
    protected $account;
    /**
     * @var int
     * @ORM\Column(name="DetectedCardID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="DisplayName", type="string", length=255, nullable=false)
     */
    private $displayname;

    /**
     * @var string
     * @ORM\Column(name="Description", type="string")
     */
    private $description;

    /**
     * @var string
     * @ORM\Column(name="Code", type="string")
     */
    private $code;

    /**
     * @var SubAccount
     * @ORM\ManyToOne(targetEntity="SubAccount")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SubAccountID", referencedColumnName="SubAccountID")
     * })
     */
    private $subAccount;

    /**
     * @var CreditCard
     * @ORM\ManyToOne(targetEntity="CreditCard")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="CreditCardID", referencedColumnName="CreditCardID")
     * })
     */
    private $creditCard;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function setAccount(Account $account): DetectedCard
    {
        $this->account = $account;

        return $this;
    }

    public function getDisplayname(): string
    {
        return $this->displayname;
    }

    public function setDisplayname(string $displayname): DetectedCard
    {
        $this->displayname = $displayname;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): DetectedCard
    {
        $this->description = $description;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): DetectedCard
    {
        $this->code = $code;

        return $this;
    }

    public function getSubAccount(): Subaccount
    {
        return $this->subAccount;
    }

    public function setSubAccount(Subaccount $subAccount): DetectedCard
    {
        $this->subAccount = $subAccount;

        return $this;
    }

    public function getCreditCard(): CreditCard
    {
        return $this->creditCard;
    }

    public function setCreditCard(CreditCard $creditCard): DetectedCard
    {
        $this->creditCard = $creditCard;

        return $this;
    }
}

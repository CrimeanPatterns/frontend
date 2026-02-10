<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AwardWallet\MainBundle\Entity\BusinessInfo.
 *
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\BusinessInfoRepository")
 * @ORM\Table(
 *     name="BusinessInfo",
 *     indexes={
 *        @ORM\Index(name="BusInfo_UserID_FK", columns={"UserID"}),
 *     }
 * )
 */
class BusinessInfo
{
    public const TRIAL_PERIOD_MONTH = 3;

    /**
     * @var int
     * @ORM\Column(name="BusinessInfoID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var float
     * @ORM\Column(name="Balance", type="decimal", length=12, scale=2, nullable=false)
     * @Assert\NotBlank()
     */
    protected $balance;

    /**
     * @var int
     * @ORM\Column(name="Discount", type="integer", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Type(type="numeric")
     * @Assert\GreaterThan(value = 0)
     * @Assert\LessThanOrEqual(value = 100)
     */
    protected $discount = 0;

    /**
     * @var \DateTime
     * @ORM\Column(name="TrialEndDate", type="datetime", nullable=true)
     */
    protected $trialEndDate;

    /**
     * Бизнес оплачен до этой даты, включительно.
     *
     * @var \DateTime
     * @ORM\Column(name="PaidUntilDate", type="date", nullable=true)
     */
    protected $paidUntilDate;

    /**
     * @var Usr
     * @ORM\OneToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $user;

    /**
     * @ORM\Column(name="APIEnabled", type="boolean", nullable=false)
     */
    protected $apiEnabled = false;

    /**
     * @ORM\Column(name="APIInviteEnabled", type="boolean", nullable=false)
     */
    protected $apiInviteEnabled = false;

    /**
     * @ORM\Column(name="APIKey", type="string", nullable=false)
     */
    protected $apiKey = '';

    /**
     * Version 1:
     *      - first one, insecure
     * Version 2:
     *      - oauth-like user connection process, should be started with /create-auth-url.
     *
     * @ORM\Column(name="APIVersion", type="integer", nullable=false)
     */
    protected $apiVersion = 1;

    /**
     * @ORM\Column(name="APIAllowIp", type="string", nullable=false)
     */
    protected $apiAllowIp = '';

    /**
     * @ORM\Column(name="APICallbackUrl", type="string", nullable=false)
     */
    protected $apiCallbackUrl = '';

    /**
     * @var string
     * @ORM\Column(name="PublicKey", type="string", length=8000, nullable=true)
     */
    protected $publicKey;

    public function __construct(Usr $business, $balance = 0, $discount = 0, ?\DateTime $trialEndDate = null)
    {
        $this->setUser($business);
        $this->setBalance($balance);
        $this->setDiscount($discount);
        $this->setTrialEndDate($trialEndDate);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @return BusinessInfo
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * @return int
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * @param int $discount
     * @return BusinessInfo
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTrialEndDate()
    {
        return $this->trialEndDate;
    }

    /**
     * @return BusinessInfo
     */
    public function setTrialEndDate(?\DateTime $trialEndDate = null)
    {
        $this->trialEndDate = $trialEndDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getPaidUntilDate()
    {
        return $this->paidUntilDate;
    }

    /**
     * @return BusinessInfo
     */
    public function setPaidUntilDate(?\DateTime $paidUntilDate = null)
    {
        $this->paidUntilDate = $paidUntilDate;

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
     * @return BusinessInfo
     */
    public function setUser(Usr $user)
    {
        $this->user = $user;

        return $this;
    }

    public function isTrial()
    {
        return isset($this->trialEndDate) && $this->trialEndDate > new \DateTime();
    }

    public function isInsufficientBalance()
    {
        return $this->balance < 0;
    }

    /**
     * @return bool
     */
    public function isPaid(?\DateTime $date = null)
    {
        if (empty($this->paidUntilDate)) {
            return false;
        }

        if (empty($date)) {
            $date = new \DateTime();
        }

        $diff = $date->diff($this->paidUntilDate);

        return ($diff->days > 0 && !$diff->invert) || $diff->days == 0;
    }

    public function isBlocked()
    {
        if ($this->getDiscount() >= 100) {
            return false;
        } // at least 1 member always exists

        if ($this->isPaid()) {
            return false;
        }

        if ($this->isTrial()) {
            return false;
        }

        return true;
    }

    public function getApiCallbackUrl()
    {
        return $this->apiCallbackUrl;
    }

    /**
     * @return $this
     */
    public function setApiCallbackUrl($apiCallbackUrl)
    {
        $this->apiCallbackUrl = $apiCallbackUrl;

        return $this;
    }

    public function getApiAllowIp()
    {
        return $this->apiAllowIp;
    }

    /**
     * @return $this
     */
    public function setApiAllowIp($apiAllowIp)
    {
        $this->apiAllowIp = $apiAllowIp;

        return $this;
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @return $this
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @return bool
     */
    public function getApiEnabled()
    {
        return $this->apiEnabled;
    }

    /**
     * @return bool
     */
    public function isApiEnabled()
    {
        return $this->apiEnabled;
    }

    /**
     * @param bool $apiEnabled
     * @return $this
     */
    public function setApiEnabled($apiEnabled)
    {
        $this->apiEnabled = $apiEnabled;

        return $this;
    }

    public function isApiInviteEnabled()
    {
        return $this->apiInviteEnabled;
    }

    /**
     * @param bool $apiInviteEnabled
     * @return $this
     */
    public function setApiInviteEnabled($apiInviteEnabled)
    {
        $this->apiInviteEnabled = $apiInviteEnabled;

        return $this;
    }

    /**
     * @return string
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @param string $publicKey
     */
    public function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    public function getApiVersion(): int
    {
        return $this->apiVersion;
    }
}

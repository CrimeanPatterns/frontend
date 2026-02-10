<?php

namespace AwardWallet\MainBundle\Form\Model;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Currency;
use AwardWallet\MainBundle\Entity\OwnableTrait;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Validator\Constraints as AwAssert;
use AwardWallet\MobileBundle\Form\Model\EntityContainerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Class ProviderCouponModel.
 *
 * @AwAssert\ConstraintReference(
 *     sourceClass = Providercoupon::class,
 *     sourceProperty = {
 *         "programname",
 *         "kind",
 *         "typeid",
 *         "cardnumber"
 *     }
 * )
 */
class ProviderCouponModel implements EntityContainerInterface
{
    use OwnableTrait {
        setOwner as protected traitSetOwner;
    }

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $value;

    /**
     * @var \DateTime
     */
    private $expirationDate;

    /**
     * @var int
     */
    private $dontTrackExpiration;

    /**
     * @var string
     */
    private $programname;

    /**
     * @var string
     */
    private $kind;

    /**
     * @var Useragent
     */
    private $useragentid;

    /**
     * @var Usr
     */
    private $userid;

    /**
     * @var int
     */
    private $typeid;

    private $typeName;

    /**
     * @var string
     */
    private $cardnumber;

    /**
     * @var int
     */
    private $pin;

    /**
     * @var Useragent[]|Collection<Useragent>
     */
    private $useragents;

    /**
     * @var Account|null
     */
    private $account;

    /**
     * @var Providercoupon
     */
    private $entity;

    /** @var Currency|null */
    private $currency;

    /**
     * @var bool
     */
    private $isarchived;

    /**
     * @return ProviderCouponModel
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return ProviderCouponModel
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return ProviderCouponModel
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * @param \DateTime $expirationDate
     * @return ProviderCouponModel
     */
    public function setExpirationDate($expirationDate)
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    /**
     * @return int
     */
    public function getDontTrackExpiration()
    {
        return $this->dontTrackExpiration;
    }

    /**
     * @param string $dontTrackExpiration
     * @return ProviderCouponModel
     */
    public function setDontTrackExpiration($dontTrackExpiration)
    {
        $this->dontTrackExpiration = $dontTrackExpiration;

        return $this;
    }

    /**
     * @return string
     */
    public function getProgramName()
    {
        return $this->programname;
    }

    /**
     * @param string $programName
     * @return ProviderCouponModel
     */
    public function setProgramName($programName)
    {
        $this->programname = $programName;

        return $this;
    }

    /**
     * @return string
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * @param string $kind
     * @return ProviderCouponModel
     */
    public function setKind($kind)
    {
        $this->kind = $kind;

        return $this;
    }

    /**
     * @return Useragent
     */
    public function getUseragentid()
    {
        return $this->userAgent;
    }

    /**
     * @param Useragent $useragentid
     * @return ProviderCouponModel
     */
    public function setUseragentid($useragentid)
    {
        $this->userAgent = $useragentid;

        return $this;
    }

    /**
     * @return Usr
     */
    public function getUserid()
    {
        return $this->user;
    }

    /**
     * @param Usr $userid
     * @return ProviderCouponModel
     */
    public function setUserid($userid)
    {
        if (null !== $this->user && $this->user !== $userid) {
            $this->useragents = new ArrayCollection();
        }
        $this->user = $userid;

        return $this;
    }

    /**
     * @return int
     */
    public function getTypeid()
    {
        return $this->typeid;
    }

    /**
     * @param int $typeid
     * @return ProviderCouponModel
     */
    public function setTypeid($typeid)
    {
        $this->typeid = $typeid;

        return $this;
    }

    public function getTypeName(): ?string
    {
        return $this->typeName;
    }

    public function setTypeName(?string $typeName): self
    {
        $this->typeName = $typeName;

        return $this;
    }

    /**
     * @return string
     */
    public function getCardNumber()
    {
        return $this->cardnumber;
    }

    /**
     * @param string $cardnumber
     * @return ProviderCouponModel
     */
    public function setCardNumber($cardnumber)
    {
        $this->cardnumber = $cardnumber;

        return $this;
    }

    /**
     * @return int
     */
    public function getPin()
    {
        return $this->pin;
    }

    /**
     * @param int $pin
     * @return ProviderCouponModel
     */
    public function setPin($pin)
    {
        $this->pin = $pin;

        return $this;
    }

    /**
     * @return \AwardWallet\MainBundle\Entity\Useragent[]|Collection
     */
    public function getUseragents()
    {
        return $this->useragents;
    }

    /**
     * @param \AwardWallet\MainBundle\Entity\Useragent[]|Collection $useragents
     * @return ProviderCouponModel
     */
    public function setUseragents($useragents)
    {
        $this->useragents = $useragents;

        return $this;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): ProviderCouponModel
    {
        $this->account = $account;

        return $this;
    }

    public function setOwner(?Owner $owner = null)
    {
        if (null !== $this->user && $this->user !== $owner->getUser()) {
            $this->useragents = new ArrayCollection();
        }
        $this->traitSetOwner($owner);
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getIsArchived(): ?bool
    {
        return $this->isarchived;
    }

    public function setIsArchived(bool $isArchived): self
    {
        $this->isarchived = $isArchived;

        return $this;
    }
}

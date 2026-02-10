<?php

namespace AwardWallet\MainBundle\Form\Model;

use AwardWallet\MainBundle\Entity\OwnableTrait;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Validator\Constraints as AwAssert;
use AwardWallet\MobileBundle\Form\Model\AbstractEntityAwareModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @property Providercoupon $entity
 * @AwAssert\Account
 */
class DocumentModel extends AbstractEntityAwareModel
{
    use OwnableTrait {
        setOwner as protected traitSetOwner;
    }

    use DocumentVaccineCardTrait;
    use DocumentInsuranceCardTrait;
    use DocumentVisaTrait;
    use DriversLicenseTrait;
    use PriorityPassTrait;

    /**
     * @var Owner
     */
    protected $owner;

    /**
     * @var string
     * @AwAssert\Condition(
     *     if = "this.isTraveler()",
     *     then = {
     *          @Assert\NotBlank()
     *     }
     * )
     */
    protected $travelerNumber;

    /**
     * @var string
     */
    protected $seatPreference;

    /**
     * @var string
     */
    protected $mealPreference;

    /**
     * @var string
     */
    protected $homeAirport;

    /**
     * @var string
     * @AwAssert\Condition(
     *     if = "this.isPassport()",
     *     then = {
     *          @Assert\NotBlank()
     *     }
     * )
     */
    protected $passportName;

    /**
     * @var string
     * @AwAssert\Condition(
     *     if = "this.isPassport()",
     *     then = {
     *          @Assert\NotBlank()
     *     }
     * )
     */
    protected $passportNumber;

    /**
     * @var \DateTime
     */
    protected $passportIssueDate;

    /**
     * @var string
     */
    protected $passportCountry;

    /**
     * @var \DateTime
     */
    protected $expirationDate;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var Useragent
     */
    private $useragentid;

    /**
     * @var Usr
     */
    private $userid;

    /**
     * @var Useragent[]|Collection<Useragent>
     */
    private $useragents;

    /**
     * @var bool
     */
    private $isarchived;

    /**
     * @return Useragent
     */
    public function getUseragentid()
    {
        return $this->userAgent;
    }

    public function isTraveler(): bool
    {
        return $this->is(Providercoupon::TYPE_TRUSTED_TRAVELER);
    }

    public function isPassport(): bool
    {
        return $this->is(Providercoupon::TYPE_PASSPORT);
    }

    public function isVaccineCard(): bool
    {
        return $this->is(Providercoupon::TYPE_VACCINE_CARD);
    }

    /**
     * @param Useragent $useragentid
     * @return DocumentModel
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
     * @return DocumentModel
     */
    public function setUserid($userid)
    {
        if (null !== $this->user && $this->user !== $userid) {
            $this->useragents = new ArrayCollection();
        }
        $this->user = $userid;

        return $this;
    }

    public function getTravelerNumber(): ?string
    {
        return $this->travelerNumber;
    }

    public function setTravelerNumber(?string $travelerNumber): DocumentModel
    {
        $this->travelerNumber = $travelerNumber;

        return $this;
    }

    public function getSeatPreference(): ?string
    {
        return $this->seatPreference;
    }

    public function setSeatPreference(?string $seatPreference): DocumentModel
    {
        $this->seatPreference = $seatPreference;

        return $this;
    }

    public function getMealPreference(): ?string
    {
        return $this->mealPreference;
    }

    public function setMealPreference(?string $mealPreference): DocumentModel
    {
        $this->mealPreference = $mealPreference;

        return $this;
    }

    public function getHomeAirport(): ?string
    {
        return $this->homeAirport;
    }

    public function setHomeAirport(?string $homeAirport): DocumentModel
    {
        $this->homeAirport = $homeAirport;

        return $this;
    }

    public function getPassportName(): ?string
    {
        return $this->passportName;
    }

    public function setPassportName(?string $passportName): DocumentModel
    {
        $this->passportName = $passportName;

        return $this;
    }

    public function getPassportNumber(): ?string
    {
        return $this->passportNumber;
    }

    public function setPassportNumber(?string $passportNumber): DocumentModel
    {
        $this->passportNumber = $passportNumber;

        return $this;
    }

    public function getPassportIssueDate(): ?\DateTime
    {
        return $this->passportIssueDate;
    }

    public function setPassportIssueDate(?\DateTime $passportIssueDate): DocumentModel
    {
        $this->passportIssueDate = $passportIssueDate;

        return $this;
    }

    public function getPassportCountry(): ?string
    {
        return $this->passportCountry;
    }

    public function setPassportCountry(?string $passportCountry): DocumentModel
    {
        $this->passportCountry = $passportCountry;

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
     * @return DocumentModel
     */
    public function setUseragents($useragents)
    {
        $this->useragents = $useragents;

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

    public function setOwner(Owner $owner): DocumentModel
    {
        if (null !== $this->user && $this->user !== $owner->getUser()) {
            $this->useragents = new ArrayCollection();
        }

        return $this->traitSetOwner($owner);
    }

    public function getExpirationDate(): ?\DateTime
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(?\DateTime $expirationDate): DocumentModel
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): DocumentModel
    {
        $this->description = $description;

        return $this;
    }

    protected function is(int $type): bool
    {
        return $this->entity->getTypeid() === $type;
    }
}

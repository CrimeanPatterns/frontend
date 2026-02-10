<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="UsrDeleted")
 * @ORM\Entity
 */
class UserDeleted
{
    /**
     * @ORM\Column(name="UserDeletedID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $deletedId;

    /**
     * @ORM\Column(name="UserID", type="integer", nullable=false)
     */
    private int $userId;

    /**
     * @ORM\Column(name="RegistrationDate", type="datetime", nullable=false)
     */
    private \DateTime $registrationDate;

    /**
     * @ORM\Column(name="FirstName", type="string", length=30, nullable=false)
     */
    private string $firstName;

    /**
     * @ORM\Column(name="LastName", type="string", length=30, nullable=false)
     */
    private string $lastName;

    /**
     * @ORM\Column(name="Email", type="string", length=80, nullable=false)
     */
    private string $email;

    /*
     * @ORM\Column(name="CountryID", type="integer", nullable=true)
     */
    private ?int $countryId;

    /**
     * @ORM\Column(name="Accounts", type="integer", nullable=false)
     */
    private int $accounts;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private int $validMailboxesCount = 0;

    /**
     * @ORM\Column(name="DeletionDate", type="datetime", nullable=false)
     */
    private \DateTime $deletionDate;

    /**
     * @ORM\Column(name="TotalContribution", type="float", nullable=false)
     */
    private float $totalContribution = 0;

    /**
     * @ORM\Column(name="CameFrom", type="integer", nullable=true)
     */
    private ?int $cameFrom;

    /**
     * @ORM\Column(name="Referer", type="string", length=250, nullable=true)
     */
    private ?string $referer;

    /**
     * @ORM\Column(name="CardClicks", type="integer", nullable=false)
     */
    private int $cardClicks = 0;

    /**
     * @ORM\Column(name="CardApprovals", type="integer", nullable=false)
     */
    private int $cardApprovals = 0;

    /**
     * @ORM\Column(name="CardEarnings", type="float", nullable=false)
     */
    private float $cardEarnings;

    /**
     * @ORM\Column(name="Reason", type="string", length=4000, nullable=false)
     */
    private string $reason;

    public function getId(): int
    {
        return $this->deletedId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getRegistrationDate(): \DateTime
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(\DateTime $registrationDate): self
    {
        $this->registrationDate = $registrationDate;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getCountryId(): ?int
    {
        return $this->countryId;
    }

    public function setCountryId(?int $countryId): self
    {
        $this->countryId = $countryId;

        return $this;
    }

    public function getAccounts(): int
    {
        return $this->accounts;
    }

    public function setAccounts(int $accounts): self
    {
        $this->accounts = $accounts;

        return $this;
    }

    public function getValidMailboxesCount(): int
    {
        return $this->validMailboxesCount;
    }

    public function setValidMailboxesCount(int $validMailboxesCount): self
    {
        $this->validMailboxesCount = $validMailboxesCount;

        return $this;
    }

    public function getDeletionDate(): \DateTime
    {
        return $this->deletionDate;
    }

    public function setDeletionDate(\DateTime $deletionDate): self
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    public function getTotalContribution(): float
    {
        return $this->totalContribution;
    }

    public function setTotalContribution(float $totalContribution): self
    {
        $this->totalContribution = $totalContribution;

        return $this;
    }

    public function getCameFrom(): ?int
    {
        return $this->cameFrom;
    }

    public function setCameFrom(?int $cameFrom): self
    {
        $this->cameFrom = $cameFrom;

        return $this;
    }

    public function getReferer(): ?string
    {
        return $this->referer;
    }

    public function setReferer(?string $referer): self
    {
        $this->referer = $referer;

        return $this;
    }

    public function getCardClicks(): int
    {
        return $this->cardClicks;
    }

    public function setCardClicks(int $cardClicks): self
    {
        $this->cardClicks = $cardClicks;

        return $this;
    }

    public function getCardApprovals(): int
    {
        return $this->cardApprovals;
    }

    public function setCardApprovals(int $cardApprovals): self
    {
        $this->cardApprovals = $cardApprovals;

        return $this;
    }

    public function getCardEarnings(): float
    {
        return $this->cardEarnings;
    }

    public function setCardEarnings(float $cardEarnings): self
    {
        $this->cardEarnings = $cardEarnings;

        return $this;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }
}

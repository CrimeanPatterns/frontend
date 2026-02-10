<?php

namespace AwardWallet\MainBundle\Form\Model;

trait DocumentVisaTrait
{
    protected ?string $countryVisa;
    protected ?string $numberEntries;
    protected ?string $fullName;
    protected ?\DateTime $issueDate;
    protected ?\DateTime $validFrom;
    protected ?string $visaNumber;
    protected ?string $category;
    protected ?int $durationInDays;
    protected ?string $issuedIn;

    public function getCountryVisa(): ?string
    {
        return $this->countryVisa;
    }

    public function setCountryVisa(?string $countryVisa): self
    {
        $this->countryVisa = $countryVisa;

        return $this;
    }

    public function getNumberEntries(): ?string
    {
        return $this->numberEntries;
    }

    public function setNumberEntries(?string $numberEntries): self
    {
        $this->numberEntries = $numberEntries;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getIssueDate(): ?\DateTime
    {
        return $this->issueDate;
    }

    public function setIssueDate(?\DateTime $issueDate): self
    {
        $this->issueDate = $issueDate;

        return $this;
    }

    public function getValidFrom(): ?\DateTime
    {
        return $this->validFrom;
    }

    public function setValidFrom(?\DateTime $validFrom): self
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    public function getVisaNumber(): ?string
    {
        return $this->visaNumber;
    }

    public function setVisaNumber(?string $visaNumber): self
    {
        $this->visaNumber = $visaNumber;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getDurationInDays(): ?int
    {
        return $this->durationInDays;
    }

    public function setDurationInDays(?int $durationInDays): self
    {
        $this->durationInDays = $durationInDays;

        return $this;
    }

    public function getIssuedIn(): ?string
    {
        return $this->issuedIn;
    }

    public function setIssuedIn(?string $issuedIn): self
    {
        $this->issuedIn = $issuedIn;

        return $this;
    }
}

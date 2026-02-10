<?php

namespace AwardWallet\MainBundle\Form\Model;

trait DocumentVaccineCardTrait
{
    protected ?string $disease;
    protected ?\DateTime $firstDoseDate;
    protected ?string $firstDoseVaccine;
    protected ?\DateTime $secondDoseDate;
    protected ?string $secondDoseVaccine;
    protected ?\DateTime $boosterDate;
    protected ?string $boosterVaccine;
    protected ?\DateTime $secondBoosterDate;
    protected ?string $secondBoosterVaccine;
    protected ?string $vaccinePassportName;
    protected ?string $vaccinePassportNumber;
    protected ?\DateTime $dateOfBirth;
    protected ?\DateTime $certificateIssued;
    protected ?string $countryIssue;

    public function getDisease(): ?string
    {
        return $this->disease;
    }

    public function setDisease(?string $disease): self
    {
        $this->disease = $disease;

        return $this;
    }

    public function getFirstDoseDate(): ?\DateTime
    {
        return $this->firstDoseDate;
    }

    public function setFirstDoseDate(?\DateTime $firstDoseDate): self
    {
        $this->firstDoseDate = $firstDoseDate;

        return $this;
    }

    public function getFirstDoseVaccine(): ?string
    {
        return $this->firstDoseVaccine;
    }

    public function setFirstDoseVaccine(?string $firstDoseVaccine): self
    {
        $this->firstDoseVaccine = $firstDoseVaccine;

        return $this;
    }

    public function getSecondDoseDate(): ?\DateTime
    {
        return $this->secondDoseDate;
    }

    public function setSecondDoseDate(?\DateTime $secondDoseDate): self
    {
        $this->secondDoseDate = $secondDoseDate;

        return $this;
    }

    public function getSecondDoseVaccine(): ?string
    {
        return $this->secondDoseVaccine;
    }

    public function setSecondDoseVaccine(?string $secondDoseVaccine): self
    {
        $this->secondDoseVaccine = $secondDoseVaccine;

        return $this;
    }

    public function getBoosterDate(): ?\DateTime
    {
        return $this->boosterDate;
    }

    public function setBoosterDate(?\DateTime $boosterDate): self
    {
        $this->boosterDate = $boosterDate;

        return $this;
    }

    public function getBoosterVaccine(): ?string
    {
        return $this->boosterVaccine;
    }

    public function setBoosterVaccine(?string $boosterVaccine): self
    {
        $this->boosterVaccine = $boosterVaccine;

        return $this;
    }

    public function getSecondBoosterDate(): ?\DateTime
    {
        return $this->secondBoosterDate;
    }

    public function setSecondBoosterDate(?\DateTime $secondBoosterDate): self
    {
        $this->secondBoosterDate = $secondBoosterDate;

        return $this;
    }

    public function getSecondBoosterVaccine(): ?string
    {
        return $this->secondBoosterVaccine;
    }

    public function setSecondBoosterVaccine(?string $secondBoosterVaccine): self
    {
        $this->secondBoosterVaccine = $secondBoosterVaccine;

        return $this;
    }

    public function getDateOfBirth(): ?\DateTime
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(?\DateTime $dateOfBirth): self
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    public function getCertificateIssued(): ?\DateTime
    {
        return $this->certificateIssued;
    }

    public function setCertificateIssued(?\DateTime $certificateIssued): self
    {
        $this->certificateIssued = $certificateIssued;

        return $this;
    }

    public function getCountryIssue(): ?string
    {
        return $this->countryIssue;
    }

    public function setCountryIssue(?string $countryIssue): self
    {
        $this->countryIssue = $countryIssue;

        return $this;
    }

    public function getVaccinePassportName(): ?string
    {
        return $this->vaccinePassportName;
    }

    public function setVaccinePassportName(?string $passportName): self
    {
        $this->vaccinePassportName = $passportName;

        return $this;
    }

    public function getVaccinePassportNumber(): ?string
    {
        return $this->vaccinePassportNumber;
    }

    public function setVaccinePassportNumber(?string $passportNumber): self
    {
        $this->vaccinePassportNumber = $passportNumber;

        return $this;
    }
}

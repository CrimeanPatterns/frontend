<?php

namespace AwardWallet\MainBundle\Form\Model;

trait DriversLicenseTrait
{
    protected ?string $country;
    protected ?string $state;
    protected ?bool $internationalLicense;
    protected ?string $licenseNumber;
    // protected ?\DateTime $dateOfBirth;
    // protected ?\DateTime $issueDate;
    // protected ?string $fullName;
    protected ?string $sex;
    protected ?string $eyes;
    protected ?string $height;
    protected ?string $class;
    protected ?bool $organDonor;

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function isInternationalLicense(): ?bool
    {
        return $this->internationalLicense;
    }

    public function setInternationalLicense(?bool $internationalLicense): self
    {
        $this->internationalLicense = $internationalLicense;

        return $this;
    }

    public function getLicenseNumber(): ?string
    {
        return $this->licenseNumber;
    }

    public function setLicenseNumber(?string $licenseNumber): self
    {
        $this->licenseNumber = $licenseNumber;

        return $this;
    }

    public function getSex(): ?string
    {
        return $this->sex;
    }

    public function setSex(?string $sex): self
    {
        $this->sex = $sex;

        return $this;
    }

    public function getEyes(): ?string
    {
        return $this->eyes;
    }

    public function setEyes(?string $eyes): self
    {
        $this->eyes = $eyes;

        return $this;
    }

    public function getHeight(): ?string
    {
        return $this->height;
    }

    public function setHeight(?string $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setClass(?string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function isOrganDonor(): ?bool
    {
        return $this->organDonor;
    }

    public function setOrganDonor(?bool $isOrganDonor): self
    {
        $this->organDonor = $isOrganDonor;

        return $this;
    }
}

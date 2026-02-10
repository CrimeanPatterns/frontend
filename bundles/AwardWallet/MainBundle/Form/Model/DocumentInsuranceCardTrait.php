<?php

namespace AwardWallet\MainBundle\Form\Model;

trait DocumentInsuranceCardTrait
{
    protected ?string $insuranceType;
    protected ?string $insuranceCompany;
    protected ?string $nameOnCard;
    protected ?string $memberNumber;
    protected ?string $groupNumber;
    protected ?string $policyHolder;
    protected ?string $insuranceType2;
    protected ?\DateTime $effectiveDate;
    protected ?string $memberServicePhone;
    protected ?string $preauthPhone;
    protected ?string $otherPhone;

    public function getInsuranceType(): ?string
    {
        return $this->insuranceType;
    }

    public function setInsuranceType(?string $insuranceType): self
    {
        $this->insuranceType = $insuranceType;

        return $this;
    }

    public function getInsuranceCompany(): ?string
    {
        return $this->insuranceCompany;
    }

    public function setInsuranceCompany(?string $insuranceCompany): self
    {
        $this->insuranceCompany = $insuranceCompany;

        return $this;
    }

    public function getNameOnCard(): ?string
    {
        return $this->nameOnCard;
    }

    public function setNameOnCard(?string $nameOnCard): self
    {
        $this->nameOnCard = $nameOnCard;

        return $this;
    }

    public function getMemberNumber(): ?string
    {
        return $this->memberNumber;
    }

    public function setMemberNumber(?string $memberNumber): self
    {
        $this->memberNumber = $memberNumber;

        return $this;
    }

    public function getGroupNumber(): ?string
    {
        return $this->groupNumber;
    }

    public function setGroupNumber(?string $groupNumber): self
    {
        $this->groupNumber = $groupNumber;

        return $this;
    }

    public function getPolicyHolder(): ?string
    {
        return $this->policyHolder;
    }

    public function setPolicyHolder(?string $policyHolder): self
    {
        $this->policyHolder = $policyHolder;

        return $this;
    }

    public function getInsuranceType2(): ?string
    {
        return $this->insuranceType2;
    }

    public function setInsuranceType2(?string $insuranceType2): self
    {
        $this->insuranceType2 = $insuranceType2;

        return $this;
    }

    public function getEffectiveDate(): ?\DateTime
    {
        return $this->effectiveDate;
    }

    public function setEffectiveDate(?\DateTime $effectiveDate): self
    {
        $this->effectiveDate = $effectiveDate;

        return $this;
    }

    public function getMemberServicePhone(): ?string
    {
        return $this->memberServicePhone;
    }

    public function setMemberServicePhone(?string $memberServicePhone): self
    {
        $this->memberServicePhone = $memberServicePhone;

        return $this;
    }

    public function getPreauthPhone(): ?string
    {
        return $this->preauthPhone;
    }

    public function setPreauthPhone(?string $preauthPhone): self
    {
        $this->preauthPhone = $preauthPhone;

        return $this;
    }

    public function getOtherPhone(): ?string
    {
        return $this->otherPhone;
    }

    public function setOtherPhone(?string $otherPhone): self
    {
        $this->otherPhone = $otherPhone;

        return $this;
    }
}

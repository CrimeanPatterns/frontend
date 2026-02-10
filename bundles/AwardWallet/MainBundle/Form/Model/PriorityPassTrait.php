<?php

namespace AwardWallet\MainBundle\Form\Model;

trait PriorityPassTrait
{
    protected ?string $accountNumber;
    protected ?bool $isSelect;
    protected ?int $creditCardId;

    public function getAccountNumber(): ?string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(?string $accountNumber): self
    {
        $this->accountNumber = $accountNumber;

        return $this;
    }

    public function getIsSelect(): ?bool
    {
        return $this->isSelect;
    }

    public function setIsSelect(?bool $isSelect): self
    {
        $this->isSelect = $isSelect;

        return $this;
    }

    public function getCreditCardId(): ?int
    {
        return $this->creditCardId;
    }

    public function setCreditCardId(?int $creditCardId): self
    {
        $this->creditCardId = $creditCardId;

        return $this;
    }
}

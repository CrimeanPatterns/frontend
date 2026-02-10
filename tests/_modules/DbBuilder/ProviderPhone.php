<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class ProviderPhone extends AbstractDbEntity
{
    private ?User $checkedBy;

    private ?Provider $provider;

    private ?EliteLevel $eliteLevel;

    private ?Country $country;

    public function __construct(
        string $phone,
        ?Provider $provider = null,
        ?EliteLevel $eliteLevel = null,
        ?Country $country = null,
        ?User $checkedBy = null,
        array $fields = []
    ) {
        parent::__construct(array_merge([
            'Paid' => 0,
            'PhoneFor' => 1,
            'CheckedDate' => date('Y-m-d H:i:s'),
            'Valid' => 1,
        ], $fields, [
            'Phone' => $phone,
        ]));

        $this->checkedBy = $checkedBy;
        $this->provider = $provider;
        $this->eliteLevel = $eliteLevel;
        $this->country = $country;
    }

    public function getCheckedBy(): ?User
    {
        return $this->checkedBy;
    }

    public function setCheckedBy(?User $checkedBy): self
    {
        $this->checkedBy = $checkedBy;

        return $this;
    }

    public function getProvider(): ?Provider
    {
        return $this->provider;
    }

    public function setProvider(?Provider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getEliteLevel(): ?EliteLevel
    {
        return $this->eliteLevel;
    }

    public function setEliteLevel(?EliteLevel $eliteLevel): self
    {
        $this->eliteLevel = $eliteLevel;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;

        return $this;
    }
}

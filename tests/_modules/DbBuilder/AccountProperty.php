<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class AccountProperty extends AbstractDbEntity
{
    private ?ProviderProperty $providerProperty;

    public function __construct(?ProviderProperty $providerProperty, string $value, array $fields = [])
    {
        parent::__construct(array_merge($fields, ['Val' => $value]));

        $this->providerProperty = $providerProperty;
    }

    public function getProviderProperty(): ?ProviderProperty
    {
        return $this->providerProperty;
    }

    public function setProviderProperty(?ProviderProperty $providerProperty): self
    {
        $this->providerProperty = $providerProperty;

        return $this;
    }

    public static function createByCode(string $code, string $value, array $fields = []): self
    {
        return new self(
            new ProviderProperty($code),
            $value,
            $fields
        );
    }
}

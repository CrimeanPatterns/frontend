<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class CreditCard extends AbstractDbEntity
{
    private ?Provider $provider;

    public function __construct(Provider $provider, string $name, array $fields = [])
    {
        parent::__construct(array_merge($fields, [
            'Name' => $name,
            'MatchingOrder' => 1,
            'PictureVer' => 1,
            'PictureExt' => 'jpg',
        ]));

        $this->provider = $provider;
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
}

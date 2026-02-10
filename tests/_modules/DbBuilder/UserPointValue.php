<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class UserPointValue extends AbstractDbEntity
{
    private ?User $user;

    private ?Provider $provider;

    public function __construct(
        float $value,
        ?User $user = null,
        ?Provider $provider = null,
        array $fields = []
    ) {
        parent::__construct(array_merge($fields, [
            'Value' => $value,
        ]));

        $this->user = $user;
        $this->provider = $provider;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

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
}

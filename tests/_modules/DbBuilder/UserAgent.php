<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class UserAgent extends AbstractDbEntity
{
    private ?User $agent;

    private ?User $client;

    public function __construct(?User $agent = null, ?User $client = null, array $fields = [])
    {
        parent::__construct(array_merge([
            'AccessLevel' => ACCESS_WRITE,
            'IsApproved' => 1,
        ], $fields));

        $this->agent = $agent;
        $this->client = $client;
    }

    public function getAgent(): ?User
    {
        return $this->agent;
    }

    public function setAgent(?User $agent): self
    {
        $this->agent = $agent;

        return $this;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): self
    {
        $this->client = $client;

        return $this;
    }

    public static function familyMember(
        User $user,
        ?string $fn = null,
        ?string $ln = null,
        ?string $email = null
    ): self {
        return new self(
            $user,
            null,
            [
                'FirstName' => $fn,
                'LastName' => $ln,
                'Email' => $email,
                'IsApproved' => 1,
            ]
        );
    }
}

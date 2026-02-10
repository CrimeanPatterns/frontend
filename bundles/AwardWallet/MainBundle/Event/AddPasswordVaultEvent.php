<?php

namespace AwardWallet\MainBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class AddPasswordVaultEvent extends Event
{
    public const NAME = 'aw.password_vault.add';

    private $provider;
    private $login;
    private $password;
    private $login2;
    private $login3;
    private $userId;
    private $partner;
    /** @var array */
    private $answers;
    private $accountId;
    private $note;

    public function __construct(
        string $provider,
        string $login,
        string $password,
        ?string $login2 = null,
        ?string $login3 = null,
        ?int $userId = null,
        ?string $partner = null,
        array $answers = [],
        ?int $accountId = null,
        ?string $note = null
    ) {
        $this->provider = $provider;
        $this->login = $login;
        $this->password = $password;
        $this->login2 = $login2;
        $this->login3 = $login3;
        $this->userId = $userId;
        $this->partner = $partner;
        $this->answers = $answers;
        $this->accountId = $accountId;
        $this->note = $note;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getLogin2(): ?string
    {
        return $this->login2;
    }

    public function getLogin3(): ?string
    {
        return $this->login3;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getPartner(): ?string
    {
        return $this->partner;
    }

    public function getAnswers(): array
    {
        return $this->answers;
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }
}

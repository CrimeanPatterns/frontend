<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class AccountShare extends AbstractDbEntity
{
    private ?UserAgent $connection;

    private ?Account $account;

    public function __construct(?UserAgent $connection = null, ?Account $account = null, array $fields = [])
    {
        parent::__construct($fields);

        $this->connection = $connection;
        $this->account = $account;
    }

    public function getConnection(): ?UserAgent
    {
        return $this->connection;
    }

    public function setConnection(?UserAgent $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): self
    {
        $this->account = $account;

        return $this;
    }
}

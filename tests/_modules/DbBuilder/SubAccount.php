<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class SubAccount extends AbstractDbEntity
{
    /**
     * @var AccountProperty[]
     */
    private array $properties;

    public function __construct(string $code, ?float $balance = null, array $properties = [], array $fields = [])
    {
        parent::__construct(array_merge($fields, [
            'Code' => $code,
            'Balance' => $balance,
        ]));

        $this->properties = $properties;
    }

    /**
     * @return AccountProperty[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }
}

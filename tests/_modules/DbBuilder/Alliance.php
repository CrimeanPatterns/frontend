<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class Alliance extends AbstractDbEntity
{
    public function __construct(string $name, string $alias, array $fields = [])
    {
        parent::__construct(array_merge($fields, [
            'Name' => $name,
            'Alias' => $alias,
        ]));
    }
}

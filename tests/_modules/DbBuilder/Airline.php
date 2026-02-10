<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class Airline extends AbstractDbEntity
{
    public function __construct(string $FSCode, ?string $name = null, array $fields = [])
    {
        parent::__construct(array_merge([
            'Code' => $FSCode,
            'FSCode' => $FSCode,
        ], $fields, [
            'Name' => $name,
        ]));
    }
}

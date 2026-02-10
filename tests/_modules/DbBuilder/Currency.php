<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class Currency extends AbstractDbEntity
{
    public function __construct(string $name, ?string $sign, ?string $code, array $fields = [])
    {
        parent::__construct(array_merge($fields, [
            'Name' => $name,
            'Sign' => $sign,
            'Code' => $code,
        ]));
    }
}

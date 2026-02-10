<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class Country extends AbstractDbEntity
{
    public function __construct(
        string $name,
        bool $haveStates = false,
        ?string $code = null,
        array $fields = []
    ) {
        parent::__construct(array_merge($fields, [
            'Name' => $name,
            'HaveStates' => $haveStates ? 1 : 0,
            'Code' => $code,
        ]));
    }
}

<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class BusinessInfo extends AbstractDbEntity
{
    public function __construct(array $fields = [])
    {
        parent::__construct(array_merge([
            'Balance' => 0,
        ], $fields));
    }
}

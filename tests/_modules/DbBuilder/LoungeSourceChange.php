<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class LoungeSourceChange extends AbstractDbEntity
{
    public function __construct(string $property, array $fields = [])
    {
        parent::__construct(array_merge($fields, [
            'Property' => $property,
        ]));
    }
}

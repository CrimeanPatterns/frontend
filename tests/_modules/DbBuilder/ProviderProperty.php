<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class ProviderProperty extends AbstractDbEntity
{
    public function __construct(string $code, array $fields = [])
    {
        parent::__construct(array_merge([
            'SortIndex' => 0,
            'Name' => $code,
        ], $fields, ['Code' => $code]));
    }

    public static function createTyped(string $code, ?int $type, ?int $kind = null, array $fields = []): self
    {
        return new self(
            $code,
            array_merge($fields, [
                'Type' => $type,
                'Kind' => $kind,
            ])
        );
    }
}

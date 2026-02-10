<?php

namespace AwardWallet\Tests\Modules\JsonNormalizer;

class ListPrefix extends ListContains
{
    public function __construct(array $sublist)
    {
        parent::__construct($sublist, 0);
    }

    public static function getCode(): string
    {
        return 'list-prefix';
    }
}

<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

use AwardWallet\MainBundle\Globals\StringUtils;

class Coupon extends AbstractDbEntity
{
    public function __construct(?string $code = null, ?string $name = null, ?int $discount = 100, array $fields = [])
    {
        $code = $code ?? StringUtils::getRandomCode(20);
        $name = $name ?? $code;

        parent::__construct(array_merge($fields, [
            'Name' => $name,
            'Code' => $code,
            'Discount' => $discount,
        ]));
    }
}

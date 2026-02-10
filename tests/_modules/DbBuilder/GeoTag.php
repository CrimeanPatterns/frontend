<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

use AwardWallet\MainBundle\Globals\StringHandler;

class GeoTag extends AbstractDbEntity
{
    public function __construct(?string $address = null, array $fields = [])
    {
        if (is_null($address)) {
            $address = 'Address ' . StringHandler::getRandomCode(30);
        }

        parent::__construct(array_merge($fields, [
            'Address' => $address,
        ]));
    }
}

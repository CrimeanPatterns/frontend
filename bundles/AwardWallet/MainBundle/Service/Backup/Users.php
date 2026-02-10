<?php

namespace AwardWallet\MainBundle\Service\Backup;

use AwardWallet\MainBundle\Parameter\DefaultBookerParameter;

class Users
{
    private DefaultBookerParameter $defaultBookerParam;

    public function __construct(DefaultBookerParameter $defaultBookerParam)
    {
        $this->defaultBookerParam = $defaultBookerParam;
    }

    public function getExcludeUsers(): array
    {
        return [
            7, 12, 1440, 5514, 11239, 116000, 93937,
            49290, 123977, 212641, 212646, 221732, 246369,
            $this->defaultBookerParam->get(),
        ];
    }
}

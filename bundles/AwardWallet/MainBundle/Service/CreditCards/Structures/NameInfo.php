<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Structures;

class NameInfo
{
    public string $name;
    public int $count;

    public function __construct(string $name, int $count = 1)
    {
        $this->name = $name;
        $this->count = $count;
    }
}

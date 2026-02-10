<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

class LoungeTitleView extends AbstractBlockView
{
    public string $name;

    public function __construct(string $name)
    {
        parent::__construct('loungeTitle');
        $this->name = $name;
    }
}

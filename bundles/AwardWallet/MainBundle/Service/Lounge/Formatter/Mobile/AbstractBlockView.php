<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

abstract class AbstractBlockView extends AbstractView
{
    public string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }
}

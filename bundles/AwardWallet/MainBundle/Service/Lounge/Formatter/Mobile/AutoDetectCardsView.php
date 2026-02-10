<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

class AutoDetectCardsView extends AbstractView
{
    public bool $enabled;

    public string $label;

    public function __construct(bool $enabled, string $label)
    {
        $this->enabled = $enabled;
        $this->label = $label;
    }
}

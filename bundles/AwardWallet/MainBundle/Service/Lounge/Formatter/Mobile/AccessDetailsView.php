<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

class AccessDetailsView extends AbstractLoungeDetailsView
{
    public string $icon;

    public string $header;

    public AccessDescriptionView $description;

    public function __construct(string $header, AccessDescriptionView $description)
    {
        parent::__construct();
        $this->icon = 'credit-card';
        $this->header = $header;
        $this->description = $description;
    }
}

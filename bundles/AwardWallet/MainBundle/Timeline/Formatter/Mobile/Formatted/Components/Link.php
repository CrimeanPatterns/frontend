<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components;

class Link
{
    public string $href;
    public ?string $title = null;

    public function __construct(
        string $href,
        ?string $title = null
    ) {
        $this->href = $href;
        $this->title = $title;
    }
}

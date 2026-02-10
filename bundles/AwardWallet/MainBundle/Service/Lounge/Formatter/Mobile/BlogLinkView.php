<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

class BlogLinkView extends AbstractView
{
    public ?string $label;

    public string $url;

    public string $image;

    public function __construct(?string $label, string $url, string $image)
    {
        $this->label = $label;
        $this->url = $url;
        $this->image = $image;
    }
}

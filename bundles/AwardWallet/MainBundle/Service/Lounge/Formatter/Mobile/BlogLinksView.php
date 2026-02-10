<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

class BlogLinksView extends AbstractBlockView
{
    /**
     * @var BlogLinkView[]
     */
    public array $items;

    public function __construct(array $items)
    {
        parent::__construct('blogLinks');
        $this->items = $items;
    }
}

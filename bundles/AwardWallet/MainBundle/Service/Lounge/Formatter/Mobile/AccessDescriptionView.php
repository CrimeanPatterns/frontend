<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

class AccessDescriptionView extends AbstractBlockView
{
    /**
     * @var AccessIconView[]
     */
    public array $items;

    public function __construct(array $items)
    {
        parent::__construct('access');
        $this->items = $items;
    }
}

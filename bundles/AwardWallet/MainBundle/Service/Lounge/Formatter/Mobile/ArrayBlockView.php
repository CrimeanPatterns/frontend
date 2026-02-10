<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

class ArrayBlockView extends AbstractView
{
    /**
     * @var AbstractBlockView[]
     */
    public array $blocks;

    /**
     * @param AbstractBlockView[] $blocks
     */
    public function __construct(array $blocks)
    {
        $this->blocks = $blocks;
    }
}

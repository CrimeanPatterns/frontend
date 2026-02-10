<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class ApplicationIcon extends BaseBlock
{
    /**
     * @var string
     */
    public $name;

    public function __construct(string $name)
    {
        $this->setType('applicationIcon');

        $this->name = $name;
    }
}

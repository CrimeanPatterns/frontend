<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class ColorTheme extends BaseBlock
{
    /**
     * @var string
     */
    public $name;

    public function __construct(string $name)
    {
        $this->setType('colorTheme');

        $this->name = $name;
    }
}

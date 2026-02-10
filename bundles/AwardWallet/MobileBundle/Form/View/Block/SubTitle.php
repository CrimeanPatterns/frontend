<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class SubTitle extends BaseBlock
{
    use FormLinkTrait;

    /**
     * @var string
     */
    public $name;

    public $bold = false;

    public function __construct($name, $bold = false)
    {
        $this->setType('subTitle');

        $this->name = $name;
        $this->bold = $bold;
    }
}

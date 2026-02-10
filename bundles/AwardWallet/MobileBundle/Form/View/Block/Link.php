<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class Link extends BaseBlock
{
    use FormLinkTrait;

    /**
     * @var string
     */
    public $name;

    /**
     * Link constructor.
     */
    public function __construct($name)
    {
        $this->setType('formLink');

        $this->name = $name;
    }
}

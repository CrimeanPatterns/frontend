<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class GroupTitle extends BaseBlock
{
    use FormLinkTrait;

    /**
     * @var string
     */
    public $name;

    /**
     * @var bool
     */
    public $small = false;

    /**
     * GroupTitle constructor.
     *
     * @param string $name
     */
    public function __construct($name, $small = false)
    {
        $this->setType('groupTitle');

        $this->name = $name;
        $this->small = $small;
    }
}

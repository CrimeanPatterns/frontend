<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class PushNotifications extends BaseBlock
{
    use FormLinkTrait;
    /**
     * @var string
     */
    public $name;

    public function __construct($name)
    {
        $this->setType('pushNotifications');
        $this->name = $name;
    }
}

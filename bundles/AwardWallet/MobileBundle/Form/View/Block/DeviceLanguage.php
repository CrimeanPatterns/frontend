<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class DeviceLanguage extends BaseBlock
{
    /**
     * @var string
     */
    public $name;

    /**
     * DeviceLanguage constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->setType('deviceLanguage');
        $this->name = $name;
    }
}

<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class Warning extends BaseBlock
{
    /**
     * @var string
     */
    public $message;

    public function __construct($message)
    {
        $this->setType('warning');
        $this->message = $message;
    }
}

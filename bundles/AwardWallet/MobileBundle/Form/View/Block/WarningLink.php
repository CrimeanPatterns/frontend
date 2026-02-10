<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class WarningLink extends BaseBlock
{
    /**
     * @var string
     */
    public $href;

    /**
     * @var string
     */
    public $message;

    public function __construct($href, $message)
    {
        $this->setType('warningLink');
        $this->href = $href;
        $this->message = $message;
    }
}

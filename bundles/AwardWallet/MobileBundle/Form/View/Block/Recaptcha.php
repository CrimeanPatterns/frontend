<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class Recaptcha extends BaseBlock
{
    public function __construct()
    {
        $this->setType('recaptcha');
    }
}

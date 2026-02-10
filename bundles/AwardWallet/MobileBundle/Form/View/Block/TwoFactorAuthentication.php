<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class TwoFactorAuthentication extends TextProperty
{
    public function __construct($name, $text = null, $hint = null, ?array $attrs = null)
    {
        parent::__construct($name, $text, $hint, $attrs);

        $this->setType('twoFactorAuthentication');
    }
}

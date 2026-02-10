<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class AccountHeader extends BaseBlock
{
    public $providerKind;
    public ?string $providerCode = null;
    public ?string $providerName = null;
    public ?string $hint = null;

    public function __construct()
    {
        $this->setType('accountHeader');
    }
}

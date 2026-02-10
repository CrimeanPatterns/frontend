<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Value;

use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Block;

class Group extends Block
{
    public ?string $desc = null;

    public function __construct($kind, $icon, $name, $val = null, $old = null)
    {
        parent::__construct($kind, $icon, $name, $val, $old);

        $this->kind = Block::KIND_GROUP;
    }
}

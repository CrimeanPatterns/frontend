<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

class TerminalListItemView extends AbstractBlockView
{
    public string $name;

    public function __construct(string $name)
    {
        parent::__construct('terminalListItem');
        $this->name = $name;
    }
}

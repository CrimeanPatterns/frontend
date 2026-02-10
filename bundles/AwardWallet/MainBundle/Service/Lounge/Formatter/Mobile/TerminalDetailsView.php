<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

class TerminalDetailsView extends AbstractLoungeDetailsView
{
    public string $icon;

    public TerminalHeaderView $header;

    public ?string $description;

    public function __construct(TerminalHeaderView $header, ?string $description)
    {
        parent::__construct();
        $this->icon = 'terminalAndGate';
        $this->header = $header;
        $this->description = $description;
    }
}

<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

class TerminalHeaderView extends AbstractBlockView
{
    public string $terminalLabel;

    public string $terminalValue;

    public ?string $gateLabel;

    /**
     * @var string[]
     */
    public ?array $gateValue;

    /**
     * @param string[] $gateValue
     */
    public function __construct(
        string $terminalLabel,
        string $terminalValue,
        ?string $gateLabel = null,
        ?array $gateValue = null
    ) {
        parent::__construct('terminal');
        $this->terminalLabel = $terminalLabel;
        $this->terminalValue = $terminalValue;
        $this->gateLabel = $gateLabel;
        $this->gateValue = $gateValue;
    }
}

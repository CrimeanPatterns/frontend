<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Value;

use AwardWallet\MainBundle\Globals\JsonSerialize\FilterNull;

class TerminalAndGate implements \JsonSerializable
{
    use FilterNull;
    /**
     * @var string
     */
    public $terminal;
    /**
     * @var string
     */
    public $gate;

    public function __construct($terminal, $gate)
    {
        $this->terminal = $terminal;
        $this->gate = $gate;
    }
}

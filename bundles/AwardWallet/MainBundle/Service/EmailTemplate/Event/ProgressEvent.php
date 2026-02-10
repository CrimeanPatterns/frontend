<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\Event;

use Symfony\Component\EventDispatcher\Event;

class ProgressEvent extends Event
{
    private $current = 0;

    private $total = 0;

    public function __construct($current, $total)
    {
        $this->current = $current;
        $this->total = $total;
    }

    /**
     * @return int
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }
}

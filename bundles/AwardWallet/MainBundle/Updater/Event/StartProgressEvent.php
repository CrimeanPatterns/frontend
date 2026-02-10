<?php

namespace AwardWallet\MainBundle\Updater\Event;

class StartProgressEvent extends AbstractEvent
{
    /**
     * @var int
     */
    public $expectedDuration;
    /**
     * @var bool
     */
    public $checkIts;

    public function __construct($accountId, $expectedDuration, $checkIts)
    {
        parent::__construct($accountId, 'start_progress');

        $this->expectedDuration = $expectedDuration;
        $this->checkIts = $checkIts;
    }
}

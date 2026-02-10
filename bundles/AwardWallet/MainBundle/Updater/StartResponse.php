<?php

namespace AwardWallet\MainBundle\Updater;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Updater\Event\AbstractEvent;

/**
 * @property int startKey
 * @NoDI()
 */
class StartResponse
{
    /**
     * @var string
     */
    public $key;

    /**
     * @var AbstractEvent[]
     */
    public $events;

    /**
     * @var array
     */
    public $socketInfo;

    /**
     * @param string $key
     * @param AbstractEvent[] $events
     */
    public function __construct($key, array $events)
    {
        $this->key = $key;
        $this->events = $events;
    }
}

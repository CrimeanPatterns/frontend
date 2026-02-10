<?php

namespace AwardWallet\MainBundle\Updater\Event;

/**
 * Class ExtensionCompleteEvent
 * server receive extension data.
 */
class ExtensionCompleteEvent extends AbstractEvent
{
    public function __construct($accountId)
    {
        parent::__construct($accountId, 'extension_complete');
    }
}

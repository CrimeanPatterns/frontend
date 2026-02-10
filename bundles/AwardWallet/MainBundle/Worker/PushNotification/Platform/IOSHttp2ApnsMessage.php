<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\Platform;

use Apns\Message;

class IOSHttp2ApnsMessage extends Message
{
    /**
     * iOS-specific
     * Sets the APS interruption level.
     */
    public function setAPSInterruptionLevel(string $interruptionLevel): self
    {
        $this->apsBody['aps']['interruption-level'] = $interruptionLevel;

        return $this;
    }
}

<?php

namespace AwardWallet\MainBundle\Event;

use AwardWallet\MainBundle\Entity\Usr;

class UserPushDeviceChangedEvent
{
    private Usr $user;

    public function __construct(Usr $user)
    {
        $this->user = $user;
    }

    public function getUser(): Usr
    {
        return $this->user;
    }
}

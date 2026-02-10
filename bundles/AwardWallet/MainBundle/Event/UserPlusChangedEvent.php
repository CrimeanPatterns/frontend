<?php

namespace AwardWallet\MainBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class UserPlusChangedEvent extends Event
{
    public const NAME = 'user_plus_changed';
    private $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function getUserId()
    {
        return $this->userId;
    }
}

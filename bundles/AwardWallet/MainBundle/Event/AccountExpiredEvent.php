<?php

namespace AwardWallet\MainBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class AccountExpiredEvent extends Event
{
    private $expiry;
    /**
     * @var int
     */
    private $userId;

    public function __construct($expiry, $userId)
    {
        $this->expiry = $expiry;
        $this->userId = $userId;
    }

    public function getExpiry()
    {
        return $this->expiry;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }
}

<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\Event;

use Symfony\Component\EventDispatcher\Event;

class UsersFoundEvent extends Event
{
    /**
     * @var int
     */
    protected $countUsers;

    public function __construct($countUsers)
    {
        $this->countUsers = $countUsers;
    }

    /**
     * @return int
     */
    public function getCountUsers()
    {
        return $this->countUsers;
    }
}

<?php

namespace AwardWallet\MainBundle\Event;

use AwardWallet\MainBundle\Entity\AbMessage;
use Symfony\Component\EventDispatcher\Event;

class BookingMessageEvent extends Event
{
    /**
     * @var AbMessage
     */
    private $message;

    public function __construct(AbMessage $message)
    {
        $this->message = $message;
    }

    /**
     * @return AbMessage
     */
    public function getMessage()
    {
        return $this->message;
    }
}

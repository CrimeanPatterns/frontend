<?php

namespace AwardWallet\MainBundle\Event\BookingMessage;

use AwardWallet\MainBundle\Entity\AbMessage;
use Symfony\Component\EventDispatcher\Event;

class NewEvent extends Event implements BookingMessageEventInterface
{
    /**
     * @var AbMessage
     */
    private $abMessage;

    /**
     * @var array
     */
    private $extras;

    public function __construct(AbMessage $abMessage, array $extras = [])
    {
        if (null === $abMessage->getAbMessageID()) {
            throw new \InvalidArgumentException('Invalid event arguments');
        }

        $this->abMessage = $abMessage;
        $this->extras = $extras;
    }

    public function getAbMessage(): AbMessage
    {
        return $this->abMessage;
    }

    public function getExtras(): array
    {
        return $this->extras;
    }
}

<?php

namespace AwardWallet\MainBundle\Event\BookingMessage;

use AwardWallet\MainBundle\Entity\AbMessage;
use Symfony\Component\EventDispatcher\Event;

class DeleteEvent extends Event implements BookingMessageEventInterface
{
    /**
     * @var AbMessage
     */
    private $abMessage;

    /**
     * @var int
     */
    private $messageId;

    /**
     * @var array
     */
    private $extras;

    public function __construct(AbMessage $abMessage, int $messageId, array $extras = [])
    {
        if (null === $messageId) {
            throw new \InvalidArgumentException('messageId should not be null');
        }

        $this->abMessage = $abMessage;
        $this->messageId = $messageId;
        $this->extras = $extras;
    }

    public function getAbMessage(): AbMessage
    {
        return $this->abMessage;
    }

    public function getMessageId(): int
    {
        return $this->messageId;
    }

    public function getExtras(): array
    {
        return $this->extras;
    }
}

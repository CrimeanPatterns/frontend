<?php

namespace AwardWallet\MainBundle\Loyalty\Listener;

use JMS\Serializer\EventDispatcher\EventDispatcherInterface;
use JMS\Serializer\EventDispatcher\PreDeserializeEvent;

class UserDataDeserializer
{
    public function __construct()
    {
    }

    public function onDeserializeCheckAccountResponse(PreDeserializeEvent $event, string $eventName, string $loweredClass, string $format, EventDispatcherInterface $eventDispatcher)
    {
        $data = $event->getData();

        if (!empty($data['userData']) && is_string($data['userData'])) {
            $data['userData'] = json_decode($data['userData'], true);
            $event->setData($data);
        }
    }
}

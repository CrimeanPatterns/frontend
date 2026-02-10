<?php

namespace AwardWallet\MainBundle\Event\BookingMessage;

use AwardWallet\MainBundle\Entity\AbMessage;

interface BookingMessageEventInterface
{
    public function getAbMessage(): AbMessage;

    public function getExtras(): array;
}

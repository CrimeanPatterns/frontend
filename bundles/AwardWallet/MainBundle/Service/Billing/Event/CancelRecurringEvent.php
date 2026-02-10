<?php

namespace AwardWallet\MainBundle\Service\Billing\Event;

use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Contracts\EventDispatcher\Event;

class CancelRecurringEvent extends Event
{
    public const NAME = 'aw.cancel_recurring_payment';

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

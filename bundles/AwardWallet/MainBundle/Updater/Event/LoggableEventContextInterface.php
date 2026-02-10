<?php

namespace AwardWallet\MainBundle\Updater\Event;

interface LoggableEventContextInterface
{
    public function getLogContext(): array;
}

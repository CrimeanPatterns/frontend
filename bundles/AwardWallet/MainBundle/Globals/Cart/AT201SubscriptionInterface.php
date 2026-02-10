<?php

namespace AwardWallet\MainBundle\Globals\Cart;

interface AT201SubscriptionInterface
{
    public function getMonths(): int;

    public function getSavings(): ?int;
}

<?php

namespace AwardWallet\MainBundle\Timeline\Item;

interface CanCreatePlanInterface
{
    public function canCreatePlan(): bool;

    public function setCanCreatePlan(bool $canCreatePlan);
}

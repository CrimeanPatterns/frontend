<?php

namespace AwardWallet\MainBundle\Timeline\Item;

trait CanCreatePlanTrait
{
    /**
     * show 'create plan' link
     * may be hidden inside travel plans.
     */
    protected bool $canCreatePlan = false;

    public function canCreatePlan(): bool
    {
        return $this->canCreatePlan;
    }

    public function setCanCreatePlan(bool $canCreatePlan): self
    {
        $this->canCreatePlan = $canCreatePlan;

        return $this;
    }
}

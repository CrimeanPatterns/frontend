<?php

namespace AwardWallet\MainBundle\Timeline\Item;

use AwardWallet\MainBundle\Entity\Plan;

class PlanEnd extends AbstractItem implements PlanInterface
{
    protected Plan $plan;

    public function __construct(Plan $plan)
    {
        parent::__construct($plan->getId(), $plan->getEndDate());
        $this->plan = $plan;
    }

    public function getPlan(): Plan
    {
        return $this->plan;
    }

    public function setPlan(Plan $plan): self
    {
        parent::__construct($plan->getId(), $plan->getEndDate());
        $this->plan = $plan;

        return $this;
    }

    public function getPrefix(): string
    {
        return 'PE';
    }

    public function getType(): string
    {
        return Type::PLAN_END;
    }
}

<?php

namespace AwardWallet\MainBundle\Timeline\Item;

use AwardWallet\MainBundle\Entity\Plan;

class PlanStart extends AbstractItem implements PlanInterface
{
    protected Plan $plan;

    protected ?\DateTime $lastUpdated = null;

    protected ?\DateTime $startSegmentDate = null;

    protected ?\DateTime $endSegmentDate = null;

    public function __construct(Plan $plan)
    {
        parent::__construct($plan->getId(), $plan->getStartDate());
        $this->plan = $plan;
    }

    public function getPlan(): Plan
    {
        return $this->plan;
    }

    public function setPlan(Plan $plan): self
    {
        parent::__construct($plan->getId(), $plan->getStartDate());
        $this->plan = $plan;

        return $this;
    }

    public function getLastUpdated(): ?\DateTime
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(?\DateTime $lastUpdated): self
    {
        $this->lastUpdated = $lastUpdated;

        return $this;
    }

    public function getStartSegmentDate(): ?\DateTime
    {
        return $this->startSegmentDate;
    }

    public function setStartSegmentDate(?\DateTime $startSegmentDate): self
    {
        $this->startSegmentDate = $startSegmentDate;

        return $this;
    }

    public function getEndSegmentDate(): ?\DateTime
    {
        return $this->endSegmentDate;
    }

    public function setEndSegmentDate(?\DateTime $endSegmentDate): self
    {
        $this->endSegmentDate = $endSegmentDate;

        return $this;
    }

    public function getPrefix(): string
    {
        return 'PS';
    }

    public function getType(): string
    {
        return Type::PLAN_START;
    }
}

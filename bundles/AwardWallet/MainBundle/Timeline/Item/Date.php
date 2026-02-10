<?php

namespace AwardWallet\MainBundle\Timeline\Item;

class Date extends AbstractItem implements CanCreatePlanInterface
{
    use CanCreatePlanTrait;

    public function __construct(
        \DateTime $startDate,
        \DateTime $localDate,
        ?\DateTime $endDate = null,
        bool $canCreatePlan = false
    ) {
        parent::__construct($localDate->getTimestamp(), $startDate, $endDate, $localDate);
        $this->canCreatePlan = $canCreatePlan;
    }

    public function getPrefix(): string
    {
        return 'DAY';
    }

    public function getType(): string
    {
        return Type::DATE;
    }
}

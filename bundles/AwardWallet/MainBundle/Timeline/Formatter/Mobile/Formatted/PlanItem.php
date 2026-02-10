<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted;

class PlanItem extends AbstractItem
{
    /**
     * @var int
     */
    public $planId;

    /**
     * @var string
     */
    public $name;

    /**
     * @var Components\Date|Components\LocalizedDate
     */
    public $endDate;

    /**
     * @var string
     */
    public $shareCode;

    /**
     * @var string
     */
    public $duration;

    /**
     * @var bool
     */
    public $hasNotes;
}

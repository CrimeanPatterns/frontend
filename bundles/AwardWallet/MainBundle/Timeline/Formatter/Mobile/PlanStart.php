<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\PlanItem;
use AwardWallet\MainBundle\Timeline\Item\PlanStart as PlanStartItem;
use AwardWallet\MainBundle\Timeline\QueryOptions;

class PlanStart implements ItemFormatterInterface
{
    /**
     * @var BlockHelper
     */
    private $blockHelper;

    public function __construct(BlockHelper $blockHelper)
    {
        $this->blockHelper = $blockHelper;
    }

    /**
     * @param PlanStartItem $item
     * @return PlanItem
     */
    public function format($item, QueryOptions $queryOptions)
    {
        return $this->blockHelper->createFromPlan($item, $queryOptions->getFormatOptions());
    }
}

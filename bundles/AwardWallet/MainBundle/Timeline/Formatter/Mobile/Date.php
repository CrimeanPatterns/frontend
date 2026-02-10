<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Item\Date as DateItem;
use AwardWallet\MainBundle\Timeline\QueryOptions;

class Date implements ItemFormatterInterface
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
     * @param DateItem $item
     * @return Formatted\SegmentItem
     */
    public function format($item, QueryOptions $queryOptions)
    {
        $formatted = new Formatted\SegmentItem();
        $this->blockHelper->formatCommonProperties($item, $formatted);

        if ($queryOptions->getFormatOptions()->supports(FormatHandler::DETAILS_BLOCKS_V2)) {
            $formatted->startDate = $this->blockHelper->createLocalizedDate(
                DateTimeExtended::create($item->getStartDate(), $item->getTimezoneAbbr()), null, 'full'
            );
        } else {
            $formatted->startDate = new Formatted\Components\Date($item->getStartDate());
        }

        $formatted->createPlan = $item->canCreatePlan();

        return $formatted;
    }
}

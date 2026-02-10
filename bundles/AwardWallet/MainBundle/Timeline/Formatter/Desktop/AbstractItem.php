<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Desktop;

use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Item\AbstractItem as AbstractItemModel;
use AwardWallet\MainBundle\Timeline\Item\CanCreatePlanInterface;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;
use AwardWallet\MainBundle\Timeline\QueryOptions;

abstract class AbstractItem implements ItemFormatterInterface
{
    /**
     * @param AbstractItemModel $item
     */
    public function format(ItemInterface $item, QueryOptions $queryOptions)
    {
        $timezoneAbbr = $item->getTimezoneAbbr();

        $result = [
            'type' => $item->getType(),
            'id' => $item->getId(),
            'startDate' => $item->getStartDate()->getTimestamp(),
            'startTimezone' => $timezoneAbbr ? strtoupper($timezoneAbbr) : $item->getStartDate()->format("T"),
            'breakAfter' => $item->isBreakAfter(),
        ];

        if (empty($item->getEndDate())) {
            $result['endDate'] = $result['startDate'];
        } else {
            $result['endDate'] = $item->getEndDate()->getTimestamp();
        }

        if ($item instanceof CanCreatePlanInterface) {
            $result['createPlan'] = $item->canCreatePlan();
        }

        return $result;
    }

    protected function transParams(array $params)
    {
        return array_merge([
            '<gray>' => '<span>',
            '</gray>' => '</span>',
        ], $params);
    }
}

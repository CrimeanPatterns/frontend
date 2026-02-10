<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Desktop;

use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;
use AwardWallet\MainBundle\Timeline\Item\PlanEnd as PlanEndItem;

class PlanEnd extends AbstractPlan
{
    /**
     * @param PlanEndItem $item
     */
    public function format(ItemInterface $item, Timeline\QueryOptions $queryOptions)
    {
        $result = parent::format($item, $queryOptions);

        $plan = $item->getPlan();
        $result['name'] = $plan->getName();
        $result['planId'] = $plan->getId();
        $result['localDate'] = $this->localizeService->formatDateTime($item->getLocalDate(), 'full', null);
        $result['canEdit'] = $this->authorizationChecker->isGranted('EDIT', $plan);

        return $result;
    }
}

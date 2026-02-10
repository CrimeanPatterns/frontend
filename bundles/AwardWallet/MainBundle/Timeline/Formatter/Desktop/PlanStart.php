<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Desktop;

use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;
use AwardWallet\MainBundle\Timeline\Item\PlanStart as PlanStartItem;

class PlanStart extends AbstractPlan
{
    /**
     * @param PlanStartItem $item
     */
    public function format(ItemInterface $item, Timeline\QueryOptions $queryOptions)
    {
        $result = parent::format($item, $queryOptions);

        $plan = $item->getPlan();
        $result['name'] = $plan->getName();
        $result['planId'] = $plan->getId();

        if (
            !is_null($item->getStartSegmentDate())
            && !is_null($item->getEndSegmentDate())
            && ($nights = Timeline\Builder::getNights($item->getStartSegmentDate(), $item->getEndSegmentDate())) > 0) {
            $result['duration'] = sprintf('%s %s', $this->localizeService->formatNumber($nights), $this->translator->trans('nights', [
                '%count%' => $nights,
            ]));
        }

        $result['canEdit'] = $this->authorizationChecker->isGranted('EDIT', $plan);

        $notes = $plan->getNotes();
        $files = $plan->getFiles();

        if (!empty($notes) || !empty($files)) {
            $result['notes'] = [];

            if (!empty($notes)) {
                $result['notes']['text'] = $notes;
            }

            if (!empty($files)) {
                $result['notes']['files'] = $this->planFileManager->getListFiles($files);
            }
        }

        if (!empty($item->getLastUpdated())) {
            $result['lastUpdated'] = $item->getLastUpdated()->getTimestamp();
        }
        $result['localDate'] = $this->localizeService->formatDateTime($item->getLocalDate(), 'full', null);
        unset($result['startTimezone']);

        if ($result['canEdit']) {
            $result['shareCode'] = $plan->getEncodedShareCode();
        }

        return $result;
    }
}

<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Desktop;

use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary as AbstractItineraryItem;
use AwardWallet\MainBundle\Timeline\Item\CruiseTrip as CruiseTripItem;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;
use AwardWallet\MainBundle\Timeline\QueryOptions;

class CruiseTrip extends AbstractTrip
{
    /**
     * @param CruiseTripItem $item
     */
    public function format(ItemInterface $item, QueryOptions $queryOptions)
    {
        $result = parent::format($item, $queryOptions);

        $result['dep'] = $item->getDeparture();
        $result['arr'] = $item->getArrival();

        if ($item->getCruiseName() !== null) {
            $title = $this->translator->trans(/** @Desc("%duration% at sea, %cruiseName%") */ 'cruise.duration', $this->transParams([
                '%duration%' => $this->intervalFormatter->formatDuration($item->getStartDate(), $item->getEndDate(), false, true),
                '%cruiseName%' => $item->getCruiseName(),
            ]), 'trips');
        } else {
            $title = $this->translator->trans(/** @Desc("%duration% at sea") */ 'cruise.duration.without-name', $this->transParams([
                '%duration%' => $this->intervalFormatter->formatDuration($item->getStartDate(), $item->getEndDate(), false, true),
            ]), 'trips');
        }

        $result['title'] = $title;

        return $result;
    }

    /**
     * @param CruiseTripItem $item
     */
    protected function getTitle(AbstractItineraryItem $item): ?string
    {
        return null;
    }
}

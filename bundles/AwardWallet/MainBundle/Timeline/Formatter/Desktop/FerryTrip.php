<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Desktop;

use AwardWallet\MainBundle\Timeline\Formatter\Utils\TripHeaderResolver;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary as AbstractItineraryItem;
use AwardWallet\MainBundle\Timeline\Item\FerryTrip as FerryTripItem;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;
use AwardWallet\MainBundle\Timeline\QueryOptions;

class FerryTrip extends AbstractTrip
{
    public function format(ItemInterface $item, QueryOptions $queryOptions)
    {
        /** @var FerryTripItem $item */
        $result = parent::format($item, $queryOptions);

        if ($item->getSource()->getDepcode() !== null && $item->getSource()->getArrcode() !== null) {
            $result['transferFormat'] = true;
        } elseif ($stationNames = TripHeaderResolver::getStationNames($item)) {
            $result['dep'] = $stationNames['departure'];
            $result['arr'] = $stationNames['arrival'];
        }
        $result['duration'] = $this->intervalFormatter->formatDuration(
            $item->getStartDate(),
            $item->getEndDate()
        );

        return $result;
    }

    /**
     * @param FerryTripItem $item
     */
    protected function getTitle(AbstractItineraryItem $item): ?string
    {
        return TripHeaderResolver::getTitle($item);
    }
}

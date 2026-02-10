<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Desktop;

use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary as AbstractItineraryItem;

class LayoverCruise extends AbstractLayover
{
    /**
     * @param Timeline\Item\CruiseLayover $item
     */
    protected function getTitle(AbstractItineraryItem $item): ?string
    {
        return $this->translator->trans(
            /** @Desc("%location% (%duration% on land)") */
            'duration-layover-cruise-at',
            $this->transParams([
                '%duration%' => $this->intervalFormatter->formatDurationViaInterval($item->getDuration(), false, true),
                '%location%' => $item->getLocation(),
            ]),
            'trips'
        );
    }

    protected function getDetails(AbstractItineraryItem $item): array
    {
        return [];
    }

    protected function getDetailsOrder(): array
    {
        return [];
    }
}

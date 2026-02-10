<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Desktop;

use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary as AbstractItineraryItem;
use AwardWallet\MainBundle\Timeline\Item\Checkout as CheckoutItem;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;

class Checkout extends AbstractReservation
{
    /**
     * @param CheckoutItem $item
     */
    public function format(ItemInterface $item, Timeline\QueryOptions $queryOptions)
    {
        $result = parent::format($item, $queryOptions);

        if ($item->isChanged()) {
            $prev = $item->getChanges()->getPreviousValue(PropertiesList::CHECK_OUT_DATE);

            if (!empty($prev)) {
                $prevTimeValue = $this->localizeService->formatDateTime($prev, null, 'short');
                $result['prevTime'] = $prevTimeValue;
                $result['changed'] = true;
            }
        }

        return $result;
    }

    /**
     * @param CheckoutItem $item
     */
    protected function getTitle(AbstractItineraryItem $item): ?string
    {
        /** @var Reservation $itinerary */
        $itinerary = $item->getItinerary();

        return $this->translator->trans(
            /** @Desc("Check out<gray>from</gray>%hotel%") */
            'check-out-from',
            $this->transParams(['%hotel%' => $itinerary->getHotelname()]),
            'trips'
        );
    }

    protected function isFormatDetails(): bool
    {
        return false;
    }

    protected function getDetailsOrder(): array
    {
        return [];
    }
}

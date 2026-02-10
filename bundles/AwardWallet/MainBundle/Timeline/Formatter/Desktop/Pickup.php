<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Desktop;

use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary as AbstractItineraryItem;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;
use AwardWallet\MainBundle\Timeline\Item\Pickup as PickupItem;

class Pickup extends AbstractRental
{
    /**
     * @param PickupItem $item
     */
    public function format(ItemInterface $item, Timeline\QueryOptions $queryOptions)
    {
        $result = parent::format($item, $queryOptions);

        if ($item->isChanged()) {
            $prev = $item->getChanges()->getPreviousValue('PickupDatetime');

            if (!empty($prev)) {
                $prevTimeValue = $this->localizeService->formatDateTime($prev, null, 'short');
                $result['prevTime'] = $prevTimeValue;
                $result['changed'] = true;
            }
        }

        return $result;
    }

    /**
     * @param PickupItem $item
     */
    protected function getDetails(AbstractItineraryItem $item): array
    {
        $result = parent::getDetails($item);

        /** @var Rental $itinerary */
        $itinerary = $item->getItinerary();
        $formatter = $item->getContext()->getPropFormatter();

        $result = array_merge($result, [
            'columns' => [
                [
                    'type' => 'info',
                    'rows' => [
                        [
                            'type' => 'pickup',
                            'date' => $this->localizeService->formatDateTime($item->getLocalDate(), 'long', 'short'),
                            'days' => $itinerary->getDays(),
                        ],
                        [
                            'type' => 'text',
                            'text' => $itinerary->getPickuplocation(),
                        ],
                    ],
                ],
            ],
        ]);

        $hours = $itinerary->getPickuphours();

        if (!empty($hours)) {
            $result['columns'][0]['rows'][] = ['type' => 'pairs', 'pairs' => [$this->translator->trans('itineraries.rental.pickup-hours', [], 'trips') => $hours]];
        }

        $props = [
            PropertiesList::CAR_MODEL,
            PropertiesList::CAR_TYPE,
            PropertiesList::PICK_UP_FAX,
            PropertiesList::DROP_OFF_FAX,
            PropertiesList::TRAVELER_NAMES,
            PropertiesList::DISCOUNT_DETAILS,
            PropertiesList::PRICED_EQUIPMENT,
        ];
        $result = \array_merge(
            $result,
            $formatter->getExistingValues($props)
        );

        return $result;
    }

    /**
     * @param PickupItem $item
     */
    protected function getTitle(AbstractItineraryItem $item): ?string
    {
        /** @var Rental $itinerary */
        $itinerary = $item->getItinerary();

        return $this->translator->trans(
            /** @Desc("Pick-up<gray>@</gray>%location%") */
            "pick-up-at",
            $this->transParams(['%location%' => $itinerary->getRentalCompanyName(true)]),
            'trips'
        );
    }

    protected function getSegmentPhone(AbstractItineraryItem $item)
    {
        return $item->getSource()->getPickupphone();
    }

    protected function getDetailsOrder(): array
    {
        return PropertiesList::$rentalPropertiesOrder;
    }

    protected function showAIWarning(): bool
    {
        return true;
    }
}

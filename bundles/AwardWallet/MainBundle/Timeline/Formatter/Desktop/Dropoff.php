<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Desktop;

use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary as AbstractItineraryItem;
use AwardWallet\MainBundle\Timeline\Item\Dropoff as DropoffItem;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;

class Dropoff extends AbstractRental
{
    /**
     * @param DropoffItem $item
     */
    public function format(ItemInterface $item, Timeline\QueryOptions $queryOptions)
    {
        $result = parent::format($item, $queryOptions);

        if (!empty($result['details']['columns'][0]['rows'][0]['prevTime'])) {
            $result['prevTime'] = $result['details']['columns'][0]['rows'][0]['prevTime'];
        }

        return $result;
    }

    /**
     * @param DropoffItem $item
     */
    protected function getDetails(AbstractItineraryItem $item): array
    {
        $result = parent::getDetails($item);

        /** @var Rental $itinerary */
        $itinerary = $item->getItinerary();

        $result = array_merge($result, [
            'columns' => [
                [
                    'type' => 'info',
                    'rows' => [
                        [
                            'type' => 'datetime',
                            'time' => $this->localizeService->formatDateTime($item->getLocalDate(), null, 'short'),
                            'date' => $this->localizeService->formatDateTime($item->getLocalDate(), 'medium', null),
                        ],
                        [
                            'type' => 'text',
                            'text' => $itinerary->getDropofflocation(),
                        ],
                    ],
                ],
            ],
        ]);

        $hours = $itinerary->getDropoffhours();

        if (!empty($hours)) {
            $result['columns'][0]['rows'][] = ['type' => 'pairs', 'pairs' => [$this->translator->trans('itineraries.rental.dropoff-hours', [], 'trips') => $hours]];
        }

        $phone = $itinerary->getDropoffphone();

        if (!empty($phone)) {
            $result['phone'] = $phone;
        }

        if ($item->isChanged()) {
            $prev = $item->getChanges()->getPreviousValue('DropoffDatetime');

            if (!empty($prev)) {
                $prevTimeValue = $this->localizeService->formatDateTime($prev, null, 'short');

                if ($prevTimeValue != $result['columns'][0]['rows'][0]['time']) {
                    $result['columns'][0]['rows'][0]['prevTime'] = $prevTimeValue;
                    $result['changed'] = true;
                }

                $date = $this->localizeService->formatDateTime($prev, 'medium', null);

                if ($date != $result['columns'][0]['rows'][0]['date']) {
                    $result['columns'][0]['rows'][0]['prevDate'] = $date;
                    $result['changed'] = true;
                }
            }
        }

        return $result;
    }

    /**
     * @param DropoffItem $item
     */
    protected function getTitle(AbstractItineraryItem $item): ?string
    {
        /** @var Rental $itinerary */
        $itinerary = $item->getItinerary();

        return $this->translator->trans(
            /** @Desc("Drop-off<gray>@</gray>%location%") */
            "drop-off-at",
            $this->transParams(['%location%' => $itinerary->getRentalCompanyName(true)]),
            'trips'
        );
    }

    protected function getSegmentPhone(AbstractItineraryItem $item)
    {
        return $item->getSource()->getDropoffphone();
    }

    protected function getDetailsOrder(): array
    {
        return PropertiesList::$rentalPropertiesOrder;
    }
}

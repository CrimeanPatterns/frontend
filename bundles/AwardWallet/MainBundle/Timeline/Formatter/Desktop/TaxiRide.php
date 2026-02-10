<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Desktop;

use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary as AbstractItineraryItem;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;

class TaxiRide extends AbstractItinerary
{
    /**
     * @param Timeline\Item\Taxi $item
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
     * @param Timeline\Item\Taxi $item
     */
    protected function getDetails(AbstractItineraryItem $item): array
    {
        $result = parent::getDetails($item);

        /** @var Rental $itinerary */
        $itinerary = $item->getItinerary();
        $formatter = $item->getContext()->getPropFormatter();

        $days = $itinerary->getDays();
        $result = array_merge($result, [
            'columns' => [
                [
                    'type' => 'info',
                    'rows' => [
                        [
                            'type' => 'pickup.taxi',
                            'date' => $this->localizeService->formatDateTime($itinerary->getStartDate(), $days > 0 ? 'medium' : 'long', 'short'),
                            'time' => $this->localizeService->formatDateTime($itinerary->getStartDate(), null, 'short'),
                        ],
                        [
                            'type' => 'text',
                            'text' => $itinerary->getPickuplocation(),
                        ],
                    ],
                ],
                [
                    'type' => 'arrow',
                ],
                [
                    'type' => 'info',
                    'rows' => [
                        [
                            'type' => 'dropoff',
                            'time' => $this->localizeService->formatDateTime($itinerary->getEndDate(), null, 'short'),
                            'date' => $this->localizeService->formatDateTime($itinerary->getEndDate(), 'medium', null),
                        ],
                        [
                            'type' => 'text',
                            'text' => $itinerary->getDropofflocation(),
                        ],
                    ],
                ],
            ],
        ]);

        if (!empty($itinerary->getRentalCompanyName())) {
            $result['columns'][0]['rows'][] = [
                'type' => 'pairs',
                'pairs' => [$this->translator->trans('award.account.provider') => $itinerary->getRentalCompanyName()], ];
        }

        return $result;
    }

    /**
     * @param Timeline\Item\Taxi $item
     */
    protected function getTitle(AbstractItineraryItem $item): ?string
    {
        /** @var Rental $itinerary */
        $itinerary = $item->getItinerary();

        return $itinerary->getRentalCompanyName();
    }

    protected function getSegmentPhone(AbstractItineraryItem $item)
    {
        return $item->getSource()->getPickupphone();
    }

    protected function getDetailsOrder(): array
    {
        return [];
    }

    protected function showAIWarning(): bool
    {
        return true;
    }
}

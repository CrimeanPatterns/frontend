<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Desktop;

use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary as AbstractItineraryItem;
use AwardWallet\MainBundle\Timeline\Item\Event as EventItem;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;

class Event extends AbstractItinerary
{
    /**
     * @param EventItem $item
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
     * @param EventItem $item
     */
    protected function getDetails(AbstractItineraryItem $item): array
    {
        $result = parent::getDetails($item);

        /** @var Restaurant $itinerary */
        $itinerary = $item->getItinerary();
        $formatter = $item->getContext()->getPropFormatter();

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
                            'text' => $itinerary->getAddress(),
                        ],
                    ],
                ],
            ],
        ]);

        if ($itinerary->getGuestCount()) {
            $result['columns'][0]['rows'] = array_merge($result['columns'][0]['rows'], [
                [
                    'type' => 'pair',
                    'name' => 'Guests',
                    'value' => $itinerary->getGuestCount(),
                ],
            ]);
        }

        if (!StringHandler::isEmpty($seats = $formatter->getValue(PropertiesList::SEATS))) {
            $prevSeats = null;

            if ($item->isChanged()) {
                $prevValue = $formatter->getPreviousValue(PropertiesList::SEATS);

                if (!StringHandler::isEmpty($prevValue) && $prevValue != $seats) {
                    $prevSeats = $prevValue;
                    $result['changed'] = true;
                }
            }

            $result['columns'][0]['rows'] = array_merge($result['columns'][0]['rows'], [
                [
                    'type' => 'pair',
                    'name' => $this->translator->trans(
                        PropertiesList::getTranslationKeyForProperty(PropertiesList::SEATS, $item->getSource()->getType()),
                        [],
                        'trips'
                    ),
                    'value' => $seats,
                    'prevValue' => $prevSeats,
                ],
            ]);
        }

        if ($item->getEndDate()) {
            $result['columns'][] = ['type' => 'arrow'];
            $result['columns'][] = [
                'type' => 'info',
                'rows' => array_merge(
                    [
                        [
                            'type' => 'datetime',
                            'time' => $this->localizeService->formatDateTime($item->getEndDate(), null, 'short'),
                            'date' => $this->localizeService->formatDateTime($item->getEndDate(), 'medium', null),
                        ],
                    ]
                ),
            ];
        }

        $result[PropertiesList::DINER_NAME] = $formatter->getValue(PropertiesList::TRAVELER_NAMES);

        if ($this->authorizationChecker->isGranted('EDIT', $itinerary)) {
            $result[PropertiesList::GUEST_COUNT] = $formatter->getValue(PropertiesList::GUEST_COUNT);
        }

        if ($item->isChanged()) {
            foreach (['StartDate' => 0, 'EndDate' => 2] as $field => $column) {
                $prev = $item->getChanges()->getPreviousValue($field);

                if (!empty($prev) && !empty($result['columns'][$column])) {
                    $prevTimeValue = $this->localizeService->formatDateTime($prev, null, 'short');

                    if ($prevTimeValue != $result['columns'][$column]['rows'][0]['time']) {
                        $result['columns'][$column]['rows'][0]['prevTime'] = $prevTimeValue;
                        $result['changed'] = true;
                    }

                    $date = $this->localizeService->formatDateTime($prev, 'medium', null);

                    if ($date != $result['columns'][$column]['rows'][0]['date']) {
                        $result['columns'][$column]['rows'][0]['prevDate'] = $date;
                        $result['changed'] = true;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param EventItem $item
     */
    protected function getTitle(AbstractItineraryItem $item): ?string
    {
        /** @var Restaurant $itinerary */
        $itinerary = $item->getItinerary();

        return $itinerary->getName();
    }

    protected function getSegmentPhone(AbstractItineraryItem $item)
    {
        return $item->getSource()->getPhone();
    }

    protected function getDetailsOrder(): array
    {
        return PropertiesList::$restaurantPropertiesOrder;
    }

    protected function showAIWarning(): bool
    {
        return true;
    }
}

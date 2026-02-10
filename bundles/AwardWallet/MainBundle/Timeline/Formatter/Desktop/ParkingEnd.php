<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Desktop;

use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary as AbstractItineraryItem;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;
use AwardWallet\MainBundle\Timeline\Item\ParkingEnd as ParkingEndItem;

class ParkingEnd extends AbstractParking
{
    /**
     * @param ParkingEndItem $item
     */
    public function format(ItemInterface $item, Timeline\QueryOptions $queryOptions)
    {
        $result = parent::format($item, $queryOptions);

        if ($item->isChanged()) {
            $prev = $item->getChanges()->getPreviousValue('EndDatetime');

            if (!empty($prev)) {
                $prevTimeValue = $this->localizeService->formatDateTime($prev, null, 'short');
                $result['prevTime'] = $prevTimeValue;
                $result['changed'] = true;
            }
        }

        return $result;
    }

    /**
     * @param ParkingEndItem $item
     */
    protected function getDetails(AbstractItineraryItem $item): array
    {
        $result = parent::getDetails($item);

        /** @var Parking $itinerary */
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
                            'text' => $itinerary->getLocation(),
                        ],
                    ],
                ],
            ],
        ]);

        $result[PropertiesList::CAR_DESCRIPTION] = $itinerary->getCarDescription();
        $result[PropertiesList::LICENSE_PLATE] = $itinerary->getPlate();
        $result[PropertiesList::SPOT_NUMBER] = $itinerary->getSpot();
        $result[PropertiesList::TRAVELER_NAMES] = implode(', ', $itinerary->getTravelerNames());
        $result[PropertiesList::RATE_TYPE] = $itinerary->getRateType();

        return $result;
    }

    /**
     * @param ParkingEndItem $item
     */
    protected function getTitle(AbstractItineraryItem $item): ?string
    {
        /** @var Parking $itinerary */
        $itinerary = $item->getItinerary();

        return $this->translator->trans(
            /** @Desc("Car pick up<gray>@</gray>%location%") */
            "parking-ends-at",
            $this->transParams(['%location%' => $this->parkingHeaderResolver->getLocation($itinerary)]),
            'trips'
        );
    }

    protected function getSegmentPhone(AbstractItineraryItem $item)
    {
        return $item->getSource()->getPhone();
    }

    protected function getDetailsOrder(): array
    {
        return PropertiesList::$parkingPropertiesOrder;
    }
}

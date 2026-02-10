<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Desktop;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary as AbstractItineraryItem;
use AwardWallet\MainBundle\Timeline\Item\Checkin as CheckinItem;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;

class Checkin extends AbstractReservation
{
    /**
     * @param CheckinItem $item
     */
    public function format(ItemInterface $item, Timeline\QueryOptions $queryOptions)
    {
        $result = parent::format($item, $queryOptions);

        if ($item->isChanged()) {
            $prev = $item->getChanges()->getPreviousValue(PropertiesList::CHECK_IN_DATE);

            if (!empty($prev)) {
                $prevTimeValue = $this->localizeService->formatDateTime($prev, null, 'short');
                $result['prevTime'] = $prevTimeValue;
                $result['changed'] = true;
            }
        }

        return $result;
    }

    /**
     * @param CheckinItem $item
     */
    protected function getDetails(AbstractItineraryItem $item): array
    {
        $result = parent::getDetails($item);

        /** @var Reservation $itinerary */
        $itinerary = $item->getItinerary();
        $formatter = $item->getContext()->getPropFormatter();

        if ($itinerary->getGeoTagid() instanceof Geotag) {
            $geo = [
                'country' => $itinerary->getGeoTagid()->getCountry(),
                'state' => $itinerary->getGeoTagid()->getState(),
                'city' => $itinerary->getGeoTagid()->getCity(),
            ];
        }
        $result = array_merge($result, [
            'columns' => [
                [
                    'type' => 'info',
                    'rows' => [
                        [
                            'type' => 'checkin',
                            'date' => $this->localizeService->formatDateTime($item->getLocalDate(), 'long', null),
                            'nights' => $itinerary->getNights(),
                        ],
                        [
                            'type' => 'text',
                            'text' => $itinerary->getAddress(),
                            'geo' => $geo ?? null,
                        ],
                    ],
                ],
            ],
        ]);
        $props = [
            PropertiesList::FAX,
            PropertiesList::GUEST_COUNT,
            PropertiesList::KIDS_COUNT,
            PropertiesList::ROOM_COUNT,
            PropertiesList::FREE_NIGHTS,
            PropertiesList::ROOM_LONG_DESCRIPTION,
            PropertiesList::ROOM_SHORT_DESCRIPTION,
            PropertiesList::ROOM_RATE,
            PropertiesList::ROOM_RATE_DESCRIPTION,
            PropertiesList::TRAVELER_NAMES,
            PropertiesList::NON_REFUNDABLE,
        ];
        $result = \array_merge(
            $result,
            $formatter->getExistingValues($props)
        );

        if ($this->authorizationChecker->isGranted('EDIT', $itinerary)) {
            $result[PropertiesList::CANCELLATION_POLICY] = $formatter->getValue(PropertiesList::CANCELLATION_POLICY);
        }

        return $result;
    }

    /**
     * @param CheckinItem $item
     */
    protected function getTitle(AbstractItineraryItem $item): ?string
    {
        /** @var Reservation $itinerary */
        $itinerary = $item->getItinerary();

        return $this->translator->trans(/** @Desc("Check-in<gray>@</gray>%location%") */ 'check-in-at',
            $this->transParams(['%location%' => $itinerary->getHotelname()]),
            'trips'
        );
    }

    protected function getSegmentPhone(AbstractItineraryItem $item)
    {
        return $item->getSource()->getPhone();
    }

    protected function getDetailsOrder(): array
    {
        return PropertiesList::$reservationPropertiesOrder;
    }

    protected function showAIWarning(): bool
    {
        return true;
    }
}

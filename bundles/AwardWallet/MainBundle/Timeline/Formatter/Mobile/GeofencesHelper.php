<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Timeline\Item;
use Clock\ClockInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function Duration\days;

class GeofencesHelper implements TranslationContainerInterface
{
    private TranslatorInterface $translator;
    private ClockInterface $clock;

    public function __construct(
        TranslatorInterface $translator,
        ClockInterface $clock
    ) {
        $this->translator = $translator;
        $this->clock = $clock;
    }

    public function createNotifications($items)
    {
        foreach ($items as $i => $item) {
            switch (get_class($item)) {
                case Item\AirTrip::class:
                    $this->createTripNotification(
                        $item,
                        $items[$i + 1] ?? null
                    );

                    break;

                default: continue 2;
            }
        }
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('push-notifications.native-app.location-always-usage-description', 'mobile'))
                ->setDesc('Needed to alert users of gate or flight changes when they are at the airport.'),

            (new Message('push-notifications.native-app.call-action.button', 'mobile'))
                ->setDesc('Call'),

            (new Message('push-notifications.hotel.call-action.message', 'mobile'))
                ->setDesc("Call %phone% for the %hotel-name% if you need a shuttle service"),

            (new Message('push-notifications.welcome-to', 'mobile'))
                ->setDesc('Welcome to %destination%'),

            (new Message('push-notitications.welcome-to.luggage-arriving', 'mobile'))
                ->setDesc('luggage is arriving to carusel %carusel%'),

            (new Message('push-notifications.welcome-to.proceed-to', 'mobile'))
                ->setDesc('proceed to'),

            (new Message('push-notifications.welcome-to.proceed-to.terminal', 'mobile'))
                ->setDesc('Terminal %terminal%'),

            (new Message('push-notifications.welcome-to.proceed-to.gate', 'mobile'))
                ->setDesc('gate %gate%'),
        ];
    }

    protected function createTripNotification(Item\AirTrip $airSegment, ?Item\ItemInterface $nextItem = null)
    {
        $trip = $airSegment->getItinerary();
        $tripSegment = $airSegment->getSource();

        if ($airSegment->getStartDate()->getTimestamp() < $this->clock->current()->sub(days(1))->getAsSecondsInt()) {
            return;
        }

        if (!count($segments = $trip->getSegments())) {
            return;
        }

        $first = $segments->first();
        $last = $segments->last();

        $translator = $this->translator;

        if ($first === $tripSegment) {
            if (
                StringHandler::isEmpty($first->getDepcode())
                || !($geotag = $tripSegment->getDepgeotagid())
                || (null === $geotag->getLat())
                || (null === $geotag->getLng())
            ) {
                return;
            }

            $terminal = $tripSegment->getDepartureTerminal();
            $gate = $tripSegment->getDepartureGate();

            if (!isset($terminal) && !isset($gate)) {
                return;
            }

            $welcomeMessage =
                trim($translator->trans('push-notifications.welcome-to', ['%destination%' => $tripSegment->getDepcode()], 'mobile')) .
                ', ' .
                trim($translator->trans('push-notifications.welcome-to.proceed-to', [], 'mobile')) .
                ' ';

            if (isset($terminal)) {
                $messages[] = trim($translator->trans('push-notifications.welcome-to.proceed-to.terminal', ['%terminal%' => $terminal], 'mobile'));
            }

            if (isset($gate)) {
                $messages[] = trim($translator->trans('push-notifications.welcome-to.proceed-to.gate', ['%gate%' => $gate], 'mobile'));
            }

            $geotag = $tripSegment->getDepgeotagid();

            $airSegment->addGeofence((new Geofence())
                ->setLat($geotag->getLat())
                ->setLong($geotag->getLng())
                ->setRadius(2500)
                ->setStartDate($airSegment->getStartDate()->getTimestamp() - 3 * SECONDS_PER_HOUR)
                ->setEndDate($airSegment->getStartDate()->getTimestamp() + 3 * SECONDS_PER_HOUR)
                ->addNotification(
                    (new Notification())
                        ->setMessage($welcomeMessage . implode(', ', $messages))
                        ->setPayload(['tl' => 'my.T.' . $tripSegment->getTripsegmentid()])
                ));
        }

        if ($last === $tripSegment) {
            if (
                StringHandler::isEmpty($first->getArrcode())
                || !($geotag = $tripSegment->getArrgeotagid())
                || (null === $geotag->getLat())
                || (null === $geotag->getLng())
            ) {
                return;
            }

            $baggage = $tripSegment->getBaggageClaim();

            $geofence = (new Geofence())
                ->setLat($geotag->getLat())
                ->setLong($geotag->getLng())
                ->setRadius(2500)
                ->setStartDate($airSegment->getEndDate()->getTimestamp() - 3 * SECONDS_PER_HOUR)
                ->setEndDate($airSegment->getEndDate()->getTimestamp() + 3 * SECONDS_PER_HOUR);

            if (isset($baggage)) {
                $message =
                    trim($translator->trans('push-notifications.welcome-to', ['%destination%' => $tripSegment->getArrcode()], 'mobile')) .
                    ', ' .
                    trim($translator->trans('push-notitications.welcome-to.luggage-arriving', ['%carusel%' => $baggage], 'mobile'));

                $geofence->addNotification(
                    (new Notification())
                        ->setMessage($message)
                        ->setPayload(['tl' => 'my.T.' . $tripSegment->getTripsegmentid()])
                );
            }

            if (
                ($nextItem instanceof Item\Checkin)
                && (abs($nextItem->getStartDate()->getTimestamp() - $airSegment->getEndDate()->getTimestamp()) < SECONDS_PER_DAY)
            ) {
                /** @var Reservation $reservation */
                $reservation = $nextItem->getItinerary();
                // TODO: smart phone filter
                $phone = preg_replace('/[^0-9\+]/', '', $reservation->getPhone());
                $hotelName = $reservation->getHotelname();

                if (
                    !StringHandler::isEmpty($phone)
                    && !StringHandler::isEmpty($hotelName)
                ) {
                    $message = $translator->trans('push-notifications.hotel.call-action.message',
                        [
                            '%phone%' => $phone,
                            '%hotel-name%' => $hotelName,
                        ],
                        'mobile'
                    );

                    $geofence->addNotification(
                        (new Notification())
                            ->setMessage($message)
                            ->setPayload(['tl' => 'my.' . $nextItem->getId()])
                            ->setCallAction(['phone' => preg_replace('/[^0-9\+]/', '', $phone)])
                    );
                }
            }

            if ($geofence->getNotifications()) {
                $airSegment->addGeofence($geofence);
            }
        }
    }
}

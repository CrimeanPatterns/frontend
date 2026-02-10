<?php

namespace AwardWallet\Tests\Unit\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Geofence;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\GeofencesHelper;
use AwardWallet\MainBundle\Timeline\Item\AirTrip;
use AwardWallet\MainBundle\Timeline\Item\Checkin;
use AwardWallet\Tests\Unit\BaseTest;
use Clock\ClockNative;
use Prophecy\Argument;
use Symfony\Contracts\Translation\TranslatorInterface;

use function Codeception\Module\Utils\Reflection\setObjectProperty;
use function PHPUnit\Framework\assertEquals;

/**
 * @group frontend-unit
 */
class GeofencesUtilsTest extends BaseTest
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function _before()
    {
        $this->translator = $this->prophesize(TranslatorInterface::class)
            ->trans(Argument::cetera())->willReturnArgument(0)
            ->getObjectProphecy()
            ->reveal();
    }

    public function testWelcomeToDepAirportGeofence()
    {
        $this->testItems([$this->getAirTrip()], [
            [
                ['push-notifications.welcome-to, push-notifications.welcome-to.proceed-to push-notifications.welcome-to.proceed-to.terminal, push-notifications.welcome-to.proceed-to.gate'],
                ['push-notifications.welcome-to, push-notitications.welcome-to.luggage-arriving'],
            ],
        ]);
    }

    public function testWelcomeToArrAirportWithTransitionToHotel()
    {
        $reservation = (new Reservation())
            ->setCheckindate(new \DateTime('+15 hour'))
            ->setCheckoutdate(new \DateTime('+30 hour'))
            ->setPhone('+100500')
            ->setHotelname('some_hotel');
        setObjectProperty($reservation, 'id', 1);

        $checkin = new Checkin($reservation);

        $this->testItems(
            [
                $this->getAirTrip(),
                $checkin,
            ],
            [
                [
                    ['push-notifications.welcome-to, push-notifications.welcome-to.proceed-to push-notifications.welcome-to.proceed-to.terminal, push-notifications.welcome-to.proceed-to.gate'],
                    [
                        'push-notifications.welcome-to, push-notitications.welcome-to.luggage-arriving',
                        'push-notifications.hotel.call-action.message',
                    ],
                ],
                [],
            ]
        );
    }

    /**
     * @param AirTrip[] $items
     * @param string[] $itemsNotifications
     */
    protected function testItems(array $items, array $itemsNotifications)
    {
        assertEquals(count($items), count($itemsNotifications), 'invalid test data');
        $geofencesHelper = new GeofencesHelper($this->translator, new ClockNative());
        $geofencesHelper->createNotifications($items);

        foreach ($items as $item) {
            $geofences = ($item instanceof AirTrip) ? $item->getGeofences() : [];
            $actualMessages = [];

            /* @var Geofence $geofence */
            foreach ($geofences as $geofenceIdx => $geofence) {
                foreach ($geofence->getNotifications() as $notification) {
                    $actualMessages[$geofenceIdx][] = $notification->getMessage();
                }
            }
            assertEquals(current($itemsNotifications), $actualMessages);
            next($itemsNotifications);
        }
    }

    protected function getAirTrip(): AirTrip
    {
        $tripSegment = new Tripsegment();
        setObjectProperty($tripSegment, 'tripsegmentid', 1);
        $tripSegment->setDepcode('LAX');
        $tripSegment->setArrcode('JFK');
        $departureGeoTag = new Geotag();
        $departureGeoTag->setLat(0);
        $departureGeoTag->setLng(0);
        $tripSegment->setDepgeotagid($departureGeoTag);
        $arrivalGeoTag = new Geotag();
        $arrivalGeoTag->setLat(30);
        $arrivalGeoTag->setLng(30);
        $tripSegment->setArrgeotagid($arrivalGeoTag);
        $tripSegment->setDepartureGate(1);
        $tripSegment->setDepartureTerminal(2);
        $tripSegment->setBaggageClaim(3);
        $tripSegment->setDepartureDate(new \DateTime('+10 hour'));
        $tripSegment->setArrivalDate(new \DateTime('+11 hour'));
        $trip = new Trip();
        $trip->addSegment($tripSegment);

        return new AirTrip($tripSegment);
    }
}

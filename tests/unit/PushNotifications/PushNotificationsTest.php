<?php

namespace AwardWallet\Tests\Unit\PushNotifications;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Event\BookingMessage\NewEvent;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\MainBundle\Service\SocksMessaging\Client;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Notification;
use Codeception\Module\Aw;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertEqualsWithDelta;
use function PHPUnit\Framework\assertTrue;
use function PHPUnit\Framework\callback;
use function PHPUnit\Framework\once;

/**
 * Class PushNotificationListenerTest.
 *
 * @group mobile
 * @group push
 * @group frontend-unit
 *
 * TODO: remove dirty container hacks
 * TODO: split into separate listeners tests
 */
class PushNotificationsTest extends BaseNotificationListenerTest
{
    public function testBalanceUpdate()
    {
        $accountId = $this->aw->createAwAccount($this->user->getId(), Aw::TEST_PROVIDER_ID, "balance.increase", '', ['Login2' => 100]);
        $this->captureNotificationOnAccountUpdate($accountId, $notification);

        $before = round($this->db->grabFromDatabase('Account', 'Balance', ['AccountID' => $accountId]));

        $device = $this->getDevice();
        $this->aw->checkAccount($accountId);

        /** @var Notification $notification */
        $notification = @unserialize($notification);

        assertEquals('android_3', $notification->getRoutingKey());
        assertEquals($device->getMobileDeviceId(), $notification->getDeviceId());
        assertEquals('en', $notification->getDeviceLang());
        assertEquals(1, $notification->getDeviceType());
        assertEquals('keykey', $notification->getDeviceKey());
        assertEqualsWithDelta(time(), $notification->getPayload()['_ts'], 10, '');
        assertEquals('a' . $accountId, $notification->getPayload()['a']);
        assertEquals("Test Provider: +100 points (from {$before} to " . ($before + 100) . ")", $notification->getMessage());
    }

    public function testItineraryUpdate()
    {
        // MY RESTAURANT
        $accountId = $this->aw->createAwAccount($this->user->getId(), Aw::TEST_PROVIDER_ID, "future.restaurant");

        $device = $this->getDevice('3.9.11');
        $this->captureNotificationOnAccountUpdate($accountId, $notification);

        /** @var Notification $notification */
        $notification = @unserialize($notification);

        assertEquals('android_3', $notification->getRoutingKey());
        assertEquals($device->getMobileDeviceId(), $notification->getDeviceId());

        $tlId = explode('.', $notification->getPayload()['tl'][0]);
        assertCount(3, $tlId);
        assertEquals('my', $tlId[0]);
        assertEquals('E', $tlId[1]);
        assertTrue(is_numeric($tlId[2]));
    }

    public function flightSingleChangeNotificationDataProvider()
    {
        $baseTimestamp = (new \DateTimeImmutable('+10 days 12:00'));

        return [
            // seats
            'with flight number' => [
                sprintf("/Seat assignments changed from 1A to 22B on S7 Airlines flight 10175 from JFK to LAX on %s\./ims", $baseTimestamp->format('F j, Y')),
                [
                    'AirlineName' => 'S7 Airlines',
                    'Seats' => ['1A', '22B'],
                    'DepDate' => $baseTimestamp,
                    'ArrDate' => $baseTimestamp->modify('+5 hours'),
                ],
            ],
        ];
    }

    /**
     * @dataProvider flightSingleChangeNotificationDataProvider
     */
    public function testFlightSingleChangeNotification($regexp, $itineraries)
    {
        $this->assertTripNotificationOnAction($regexp, $itineraries, 'update');
    }

    public function flightSingleChangeNotificationLocalizedData()
    {
        $baseTimestamp = new \DateTimeImmutable('+1 year 15 june');

        return [
            // seats
            'with flight number' => [
                sprintf("/Seat assignments changed from 1A, 2B to 22B, 33C on S7 Airlines flight 10175 from JFK to LAX on 15 June %d\./ims", $baseTimestamp->format('Y')),
                [
                    'Seats' => ['1A,2B', '22B,33C'],
                    'AirlineName' => 'S7',
                    'DepDate' => $baseTimestamp,
                    'ArrDate' => $baseTimestamp->modify('+5 hours'),
                ],
            ],
        ];
    }

    /**
     * @dataProvider flightSingleChangeNotificationLocalizedData
     */
    public function testFlightSingleChangeNotificationLocalized(string $regexp, array $itinerariesData)
    {
        $this->user->setRegion('SE');
        $this->user->setLanguage('en');
        $this->em->flush($this->user);
        $this->assertTripNotificationOnAction($regexp, $itinerariesData, 'update');
    }

    public function testUserAgentItineraryUpdate()
    {
        $userAgent = $this->aw->createFamilyMember($this->user->getId(), 'First', 'Last');
        $accountId = $this->aw->createAwAccount($this->user->getId(), Aw::TEST_PROVIDER_ID, "future.trip.random.seats", '', ['UserAgentID' => $userAgent]);

        $device = $this->getDevice('3.9.1');
        $this->captureNotificationOnAccountUpdate($accountId, $notification);

        /** @var Notification $notification */
        $notification = @unserialize($notification);

        assertEquals('android_3', $notification->getRoutingKey());
        assertEquals($device->getMobileDeviceId(), $notification->getDeviceId());

        $tlId = explode('.', $notification->getPayload()['tl'][0]);
        assertCount(3, $tlId);
        assertEquals((string) $userAgent, $tlId[0]);
        assertEquals('T', $tlId[1]);
        assertTrue(is_numeric($tlId[2]));
    }

    public function testNewBookingMessage()
    {
        $bookerUserId = $this->aw->createAwBookerStaff('tstbkr' . $this->aw->grabRandomString(5), 'awdeveloper');
        $booker = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($bookerUserId);

        $requestId = $this->aw->createAbRequest([
            'UserID' => $this->user->getId(),
            'BookerUserID' => $this->container->get(UsrRepository::class)->getBusinessByUser($booker)->getId(),
        ]);
        $request = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->find($requestId);

        $this->getDevice('3.16.0');

        $this->container->set(Client::class, new class() extends Client {
            public function __construct()
            {
            }

            public function presence($channel): array
            {
                return [['body' => ['data' => []]]];
            }

            public function publish($channel, $data): void
            {
            }
        });

        $token = $this->container->get('security.token_storage')->getToken();
        $token->setUser($booker);
        $token->setAuthenticated(true);

        $request->addMessage($mesaage =
            (new AbMessage())
                ->setUser($booker)
                ->setCreateDate(new \DateTime())
                ->setPost("<p>some text</p>\n<p>new line</p>")
                ->setType(AbMessage::TYPE_COMMON)
                ->setFromBooker(true)
        );
        $this->em->persist($mesaage);
        $this->em->flush();

        $this->captureNotification(function () use ($mesaage) {
            $this->container->get('event_dispatcher')->dispatch(new NewEvent($mesaage), 'aw.booking.message.new');
        }, $notification);

        $notification = @unserialize($notification);

        assertEquals("Ragnar Petrovich @ Much booker:\r\nsome textnew line", $notification->getMessage());
    }

    public function testPersonalBusiness()
    {
        $bookerUserId = $this->aw->createAwBookerStaff('tstbkr' . $this->aw->grabRandomString(5), 'awdeveloper');
        $booker = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($bookerUserId);

        $requestId = $this->aw->createAbRequest(['UserID' => $this->user->getId(), 'BookerUserID' => $booker->getId()]);
        /** @var AbRequest $request */
        $request = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->find($requestId);
        $request->setAssignedUser($booker);

        $manager = $this->container->get('aw.manager.mobile_device_manager');
        $manager->addDevice($bookerUserId, 'chrome', 'chrome-business', 'en', 'web:business', '127.0.0.1');
        $manager->addDevice($this->user->getId(), 'chrome', 'chrome-personal', 'en', 'web:personal', '127.0.0.1');

        $token = $this->container->get('security.token_storage')->getToken();
        $token->setUser($booker);
        $token->setAuthenticated(true);

        $request->addMessage($message =
            (new AbMessage())
                ->setUser($booker)
                ->setCreateDate(new \DateTime())
                ->setPost("<p>some text</p>\n<p>new line</p>")
                ->setType(AbMessage::TYPE_COMMON)
                ->setFromBooker(true)
        );
        $this->em->persist($message);
        $this->em->flush();

        $notifications = [];
        $producer = $this->createMock(ProducerInterface::class);
        $producer->expects(self::atLeastOnce())->method('publish')->willReturnCallback(function ($msgBody) use (&$notifications) {
            $notifications[] = $msgBody;
        });
        $this->container->set('old_sound_rabbit_mq.push_notification_producer', $producer);

        // personal
        $this->container->get('event_dispatcher')->dispatch(new NewEvent($message), 'aw.booking.message.new');
        $this->assertCount(1, $notifications);
        /** @var Notification $notification */
        $notification = @unserialize(array_shift($notifications));
        assertEquals("Booking request #" . $requestId, $notification->getMessage());
        assertEquals("web:personal", $notification->getDeviceAppVersion());

        // business
        $message->setFromBooker(false);
        $this->em->persist($message);
        $this->em->flush();
        $this->container->get('event_dispatcher')->dispatch(new NewEvent($message), 'aw.booking.message.new');
        $this->assertCount(1, $notifications);
        /** @var Notification $notification */
        $notification = @unserialize(array_shift($notifications));
        assertEquals("Booking request #" . $requestId, $notification->getMessage());
        assertEquals("web:business", $notification->getDeviceAppVersion());

        // all
        $sender = $this->container->get(Sender::class);
        $devices = $sender->loadDevices([$this->user], MobileDevice::TYPES_ALL, Content::TYPE_FLIGHT_DELAY);
        $this->assertCount(1, $devices); // all except booking are only personal
        $devices = $sender->loadDevices([$booker], MobileDevice::TYPES_ALL, Content::TYPE_BOOKING);
        $this->assertCount(1, $devices);
    }

    protected function captureNotificationOnAccountUpdate($accountId, &$notification, $times = null)
    {
        $this->captureNotification(
            function () use ($accountId) { $this->aw->checkAccount($accountId); },
            $notification,
            $times
        );
    }

    protected function captureNotification($task, &$notification, $times = null)
    {
        $producer = $this->createMock(ProducerInterface::class);
        $producer->expects($times ?: once())->method('publish')->with($this->captureArg($notification));
        $this->container->set('old_sound_rabbit_mq.push_notification_producer', $producer);

        $task();
    }

    protected function captureArg(&$arg)
    {
        return callback(function ($argToMock) use (&$arg) {
            $arg = $argToMock;

            return true;
        });
    }
}

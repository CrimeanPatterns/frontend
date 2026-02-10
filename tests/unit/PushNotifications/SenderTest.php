<?php

namespace AwardWallet\Tests\Unit\PushNotifications;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Event\PassportExpiredEvent;
use AwardWallet\MainBundle\FrameworkExtension\Translator\Trans;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\PushEvent;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\MainBundle\Service\Notification\Spy;
use AwardWallet\MainBundle\Service\Notification\TransformedContent;
use AwardWallet\MainBundle\Service\Notification\TransformerInterface;
use AwardWallet\MainBundle\Service\SocksMessaging\Client;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use AwardWallet\Tests\Unit\BaseUserTest;
use Clock\ClockInterface;
use Clock\ClockTest;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use function Duration\seconds;

/**
 * @group push
 * @group frontend-unit
 */
class SenderTest extends BaseUserTest
{
    private ?LocalizeService $localizer = null;

    public function _before()
    {
        parent::_before();

        $this->localizer = $this->container->get(LocalizeService::class);
    }

    public function _after()
    {
        $this->localizer = null;

        parent::_after();
    }

    public function testRewardsActivityPersonal()
    {
        $this->db->haveInDatabase("MobileDevice", ["DeviceKey" => "test" . $this->user->getId(), "DeviceType" => MobileDevice::TYPE_CHROME, 'Lang' => 'en', 'UserID' => $this->user->getId(), 'AppVersion' => 'web']);
        $accountId = $this->aw->createAwAccount($this->user->getId(), "testprovider", "balance.random");
        $producer = $this->mockServiceWithBuilder("old_sound_rabbit_mq.push_notification_producer");
        $producer->expects($this->once())->method('publish');
        $this->aw->checkAccount($accountId, true, UpdaterEngineInterface::SOURCE_MOBILE);
        $this->aw->checkAccount($accountId, true, UpdaterEngineInterface::SOURCE_MOBILE);
    }

    public function testRewardsActivityBusiness()
    {
        $this->db->haveInDatabase("MobileDevice", ["DeviceKey" => "test" . $this->user->getId(), "DeviceType" => MobileDevice::TYPE_CHROME, 'Lang' => 'en', 'UserID' => $this->user->getId(), 'AppVersion' => 'web:business']);
        $accountId = $this->aw->createAwAccount($this->user->getId(), "testprovider", "balance.random");
        $producer = $this->mockServiceWithBuilder("old_sound_rabbit_mq.push_notification_producer");
        $producer->expects($this->never())->method('publish');
        $this->aw->checkAccount($accountId);
        $this->aw->checkAccount($accountId);
    }

    public function testAccountExpiration()
    {
        $this->db->haveInDatabase("MobileDevice", ["DeviceKey" => "test" . $this->user->getId(), "DeviceType" => MobileDevice::TYPE_CHROME, 'Lang' => 'en', 'UserID' => $this->user->getId(), 'AppVersion' => 'web:personal']);
        $this->db->haveInDatabase("MobileDevice", ["DeviceKey" => "test" . $this->user->getId(), "DeviceType" => MobileDevice::TYPE_IOS, 'Lang' => 'en', 'UserID' => $this->user->getId(), 'AppVersion' => '3.13.0']);

        $producer = $this->mockServiceWithBuilder("old_sound_rabbit_mq.push_notification_producer");
        $eventDispatcher = $this->container->get("event_dispatcher");

        $producer->expects($this->exactly(7))->method('publish');

        $accountId = $this->aw->createAwAccount($this->user->getId(), "testprovider", "expiration.close", null, ["ExpirationDate" => date("Y-m-d H:i:s", strtotime("+1 week"))]);
        $eventDispatcher->dispatch(new \AwardWallet\MainBundle\Event\AccountExpiredEvent([Account::class, $accountId], $this->user->getId()), 'aw.account.expire');

        $this->db->executeQuery("update Account set SubAccounts = 1 where AccountID = $accountId");
        $subAccId = $this->db->haveInDatabase("SubAccount", ["AccountID" => $accountId, 'DisplayName' => 'Test', 'Balance' => 12, 'Code' => 'test', "ExpirationDate" => date("Y-m-d H:i:s", strtotime("+1 week"))]);
        $eventDispatcher->dispatch(new \AwardWallet\MainBundle\Event\AccountExpiredEvent([Subaccount::class, $subAccId], $this->user->getId()), 'aw.account.expire');

        $couponId = $this->aw->createAwCoupon($this->user->getId(), 'Test', 'Coupon', '', ["ExpirationDate" => date("Y-m-d H:i:s", strtotime("+1 week"))]);
        $eventDispatcher->dispatch(new \AwardWallet\MainBundle\Event\AccountExpiredEvent([Providercoupon::class, $couponId], $this->user->getId()), 'aw.account.expire');

        $this->db->executeQuery("update Usr set wpExpire = 0 where UserID = " . $this->user->getId());
        $eventDispatcher->dispatch(new \AwardWallet\MainBundle\Event\AccountExpiredEvent([Account::class, $accountId], $this->user->getId()), 'aw.account.expire');
    }

    public function testPassportExpiration()
    {
        $this->db->haveInDatabase("MobileDevice", ["DeviceKey" => "test" . $this->user->getId(), "DeviceType" => MobileDevice::TYPE_CHROME, 'Lang' => 'en', 'UserID' => $this->user->getId(), 'AppVersion' => 'web:personal']);
        $this->db->haveInDatabase("MobileDevice", ["DeviceKey" => "test" . $this->user->getId(), "DeviceType" => MobileDevice::TYPE_IOS, 'Lang' => 'en', 'UserID' => $this->user->getId(), 'AppVersion' => '3.13.0']);

        $producer = $this->mockServiceWithBuilder("old_sound_rabbit_mq.push_notification_producer");
        $eventDispatcher = $this->container->get("event_dispatcher");

        $producer->expects($this->exactly(2))->method('publish');

        $passportId = $this->aw->createAwCoupon($this->user->getId(), "My Passport", null, null, ["ExpirationDate" => date("Y-m-d H:i:s", strtotime("+9 months"))]);
        $eventDispatcher->dispatch(new PassportExpiredEvent(
            $this->user->getId(),
            $passportId,
            9,
            $this->user->getFullName()
        ), 'aw.passport.expire');
    }

    public function regionalSettingsInMobileNotificationsDataProvider()
    {
        return [
            'lang: en, region: RU' => ['en', 'RU', 'You have specified that the balance on this award program is due to expire on %date%'],
            'lang: en, region: US' => ['en', 'US', 'You have specified that the balance on this award program is due to expire on %date%'],
            'lang: ru, region: RU' => ['ru', 'RU', 'Вы указали, что срок действия баланса этой программы лояльности истекает %date%'],
            'lang: ru, region: US' => ['ru', 'US', 'Вы указали, что срок действия баланса этой программы лояльности истекает %date%'],
        ];
    }

    /**
     * @dataProvider regionalSettingsInMobileNotificationsDataProvider
     */
    public function testRegionalSettingsInMobileNotifications($lang, $region, $message)
    {
        $date = new \DateTime('1 january, 12:00');
        $formattedDateTime = $this->localizer->formatDateTime($date, 'full', 'short', sprintf('%s_%s', $lang, $region));
        $message = str_replace('%date%', $formattedDateTime, $message);

        $this->user->setLanguage($lang);
        $this->user->setRegion($region);
        $this->em->flush($this->user);
        $localizer = $this->container->get(LocalizeService::class);
        $spy = $this->prophesize(Spy::class);
        $spy->getPushCopyDevices(Argument::any(), Argument::type('bool'))->willReturn([]);

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::that(function ($event) use ($message) {
            return $event instanceof PushEvent
                && $event->getUserId() === $this->user->getId()
                && $event->getMessage() === $message;
        }))->shouldBeCalledTimes(1);

        $sender = new Sender(
            $this->prophesize(EntityManagerInterface::class)->reveal(),
            $this->container->get('translator'),
            $localizer,
            $this->container->get('aw.api.versioning.mobile'),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(ProducerInterface::class)->reveal(),
            $this->prophesize(ProducerInterface::class)->reveal(),
            $this->prophesize(Client::class)->reveal(),
            $spy->reveal(),
            $eventDispatcher->reveal(),
            new ClockTest(seconds(100))
        );

        $sender->addTransformer(
            MobileDevice::TYPE_ANDROID,
            $this
                ->prophesize(TransformerInterface::class)
                ->transform(Argument::cetera())
                ->will(function ($arguments) {
                    return new TransformedContent(
                        $arguments[1]->message,
                        [],
                        Content::TYPE_ACCOUNT_EXPIRATION
                    );
                })
                ->getObjectProphecy()
                ->reveal()
        );

        $sender->send(
            new Content(
                'title',
                new Trans('account.expire.manual-warning', [
                    '%date%' => function ($id, $params, $domain, $locale) use ($localizer) {
                        return $localizer->formatDateTime(new \DateTime('1 january, 12:00'), 'full', 'short', $locale);
                    },
                ]),
                Content::TYPE_ACCOUNT_EXPIRATION,
                null,
                (new Options())
                    ->setDeadlineTimestamp(101)
            ),
            [
                (new MobileDevice())
                    ->setAppVersion('3.21.0+b100500')
                    ->setDeviceKey('1234')
                    ->setDeviceType(MobileDevice::TYPE_ANDROID)
                    ->setUser($this->user),
            ]
        );

        $this->getProphetExtended()->checkPredictions();
    }

    public function testSpy()
    {
        $spy = $this->prophesize(Spy::class);
        $spyDevice = new MobileDevice();
        $spyDevice->setDeviceType(MobileDevice::TYPE_ANDROID);
        $spyDevice->setUser($this->user);
        $spy->getPushCopyDevices(Argument::type('integer'), Argument::type('bool'))->willReturn([$spyDevice]);

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::cetera())->shouldBeCalledTimes(2);

        $sender = new Sender(
            $this->prophesize(EntityManagerInterface::class)->reveal(),
            $this->container->get('translator'),
            $this->container->get(LocalizeService::class),
            $this->container->get('aw.api.versioning.mobile'),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(ProducerInterface::class)->reveal(),
            $this->prophesize(ProducerInterface::class)->reveal(),
            $this->prophesize(Client::class)->reveal(),
            $spy->reveal(),
            $eventDispatcher->reveal(),
            new ClockTest()
        );

        $content = new Content(
            'title',
            'Hello',
            Content::TYPE_ACCOUNT_EXPIRATION
        );

        $sender->addTransformer(
            MobileDevice::TYPE_ANDROID,
            $this
                ->prophesize(TransformerInterface::class)
                ->transform(Argument::cetera())
                ->will(function ($arguments) {
                    return new TransformedContent(
                        $arguments[1]->message,
                        [],
                        Content::TYPE_ACCOUNT_EXPIRATION
                    );
                })
                ->getObjectProphecy()
                ->reveal()
        );

        $sender->send(
            $content,
            [
                (new MobileDevice())
                    ->setAppVersion('3.21.0+b100500')
                    ->setDeviceKey('1234')
                    ->setDeviceType(MobileDevice::TYPE_ANDROID)
                    ->setUser($this->user),
            ]
        );

        $this->getProphetExtended()->checkPredictions();
    }

    public function testWillNotSendOnDeadlineTimestampe()
    {
        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $em = $emProphecy->reveal();
        $emProphecy
            ->getRepository(\AwardWallet\MainBundle\Entity\MobileDevice::class)
            ->willReturn(new EntityRepository(
                $em,
                new ClassMetadata(MobileDevice::class)
            ));

        $sender = $this->makeProphesizedMuted(Sender::class, [
            ClockInterface::class => new ClockTest(seconds(100)),
            LoggerInterface::class => $this
                ->prophesize(LoggerInterface::class)
                ->info("will not send message, hit deadline", Argument::cetera())
                ->shouldBeCalledOnce()
                ->getObjectProphecy()
                ->reveal(),
            EntityManagerInterface::class => $em,
        ]);

        $content = new Content(
            'title',
            'Hello',
            Content::TYPE_ACCOUNT_EXPIRATION,
            null,
            (new Options())
                ->setDeadlineTimestamp(100)
        );

        $device = new MobileDevice();
        $device->setDeviceType(MobileDevice::TYPE_ANDROID);
        $device->setUser($this->user);

        $this->assertFalse($sender->send($content, [$device]));
        $this->getProphetExtended()->checkPredictions();
    }
}

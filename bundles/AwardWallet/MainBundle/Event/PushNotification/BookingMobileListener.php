<?php

namespace AwardWallet\MainBundle\Event\PushNotification;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\BookingMessage\NewEvent;
use AwardWallet\MainBundle\FrameworkExtension\Translator\Trans;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\MainBundle\Service\SocksMessaging\BookingMessaging;
use AwardWallet\MainBundle\Service\SocksMessaging\ClientInterface;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\InterruptionLevel;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use Psr\Log\LoggerInterface;

class BookingMobileListener
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var BookingMessaging
     */
    private $bookingMessaging;

    /**
     * @var Sender
     */
    private $sender;

    /**
     * @var ApiVersioningService
     */
    private $apiVersioning;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ClientInterface $client,
        BookingMessaging $bookingMessaging,
        Sender $sender,
        ApiVersioningService $apiVersioning,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->bookingMessaging = $bookingMessaging;
        $this->sender = $sender;
        $this->apiVersioning = $apiVersioning;
        $this->logger = $logger;
    }

    public function onBookingMessage(NewEvent $event)
    {
        /** @var AbMessage $abMessage */
        $abMessage = $event->getAbMessage();
        $abRequest = $abMessage->getRequest();
        /** @var Usr $recipient */
        $recipient = null;

        if ($abMessage->isInternal()) {
            return;
        }

        // for tests
        $booker2booker = false;

        if ($abMessage->getFromBooker()) {
            // to author
            $recipient = $abMessage->getRequest()->getUser();
            $booker2booker = $recipient && $recipient->getUserid() === $abMessage->getUser()->getUserid();
        } else {
            // to booker
            if ($abRequest->getAssignedUser()) {
                $recipient = $abRequest->getAssignedUser();
            }
        }

        if (!$recipient) {
            return;
        }

        // don't send if online connection is alive
        $presence = $this->client->presence($this->bookingMessaging->getOnlineChannel($abRequest));

        foreach ($presence[0]['body']['data'] as $item) {
            if ((int) $item['user'] === $recipient->getUserid() && !$booker2booker) {
                return;
            }
        }

        $devices = array_filter(
            $this->sender->loadDevices([$recipient], MobileDevice::TYPES_MOBILE, Content::TYPE_BOOKING, $abMessage->getFromBooker()),
            function (MobileDevice $device) {
                return $this->apiVersioning->versionStringSupports($device->getAppVersion(), MobileVersions::BOOKING_VIEW);
            }
        );

        if (!$devices) {
            return;
        }

        $this->sender->send(
            new Content(
                new Trans('request.no', ['%id%' => $abRequest->getAbRequestID()], 'booking'),
                $abMessage->getPost(),
                Content::TYPE_BOOKING,
                $abMessage,
                (new Options())
                    ->setPriority(8)
                    ->setInterruptionLevel(InterruptionLevel::TIME_SENSITIVE)
            ),
            $devices
        );
    }
}

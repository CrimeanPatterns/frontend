<?php

namespace AwardWallet\MainBundle\Service\WebPush;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\BookingMessage\NewEvent;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\MainBundle\Service\SocksMessaging\BookingMessaging;
use AwardWallet\MainBundle\Service\SocksMessaging\ClientInterface;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\InterruptionLevel;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class WebPushListener
{
    private ClientInterface $client;

    private BookingMessaging $bookingMessaging;

    private TranslatorInterface $translator;

    private EntityManagerInterface $em;

    private RouterInterface $router;

    private Sender $sender;

    private LoggerInterface $logger;

    public function __construct(
        ClientInterface $client,
        BookingMessaging $bookingMessaging,
        TranslatorInterface $translator,
        EntityManagerInterface $em,
        RouterInterface $router,
        Sender $sender,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->bookingMessaging = $bookingMessaging;
        $this->translator = $translator;
        $this->em = $em;
        $this->router = $router;
        $this->sender = $sender;
        $this->logger = $logger;
    }

    public function onBookingNewMessage(NewEvent $event)
    {
        /** @var AbMessage $message */
        $message = $event->getAbMessage();
        $request = $message->getRequest();

        $this->logger->info("sending web push for new booking message");

        $text = $this->translator->trans(/** @Desc("You have a new message.") */ 'have.new.message');

        $striped = StringHandler::strLimit(trim(CleanXMLValue(str_replace(["\n", "\r", "\t"], ' ', html_entity_decode(strip_tags($message->getPost()))))), 200);

        if (strlen($striped) > 0) {
            $text = $striped;
        }

        $users = $this->getUsers($request, $message);

        if (!empty($users)) {
            $this->sender->send(
                new Content(
                    $this->translator->trans(/** @Desc("Booking request #%id%") */ 'request.no', ['%id%' => $request->getAbRequestID()], 'booking'),
                    $text,
                    Content::TYPE_BOOKING,
                    $message,
                    (new Options())
                        ->setPriority(8)
                        ->setInterruptionLevel(InterruptionLevel::TIME_SENSITIVE)
                ),
                $this->sender->loadDevices($users, MobileDevice::TYPES_DESKTOP, Content::TYPE_BOOKING, $message->getFromBooker())
            );
        }
    }

    /**
     * @return Usr[]
     */
    private function getUsers(AbRequest $request, AbMessage $message)
    {
        if ($message->isInternal()) {
            return [];
        }

        /** @var Usr $recipient */
        $recipient = null;

        // for tests
        $booker2booker = false;

        if ($message->getFromBooker()) {
            // to author
            $recipient = $message->getRequest()->getUser();
            $booker2booker = $recipient && $recipient->getUserid() === $message->getUser()->getUserid();
        } else {
            // to booker
            if ($request->getAssignedUser()) {
                $recipient = $request->getAssignedUser();
            }
        }

        if (!$recipient) {
            return [];
        }

        $online = $this->getUserIdsOnline($request);

        foreach ($online as $onlineUser) {
            if ($onlineUser === $recipient->getUserid() && !$booker2booker) {
                return [];
            }
        }

        $this->logger->debug("selected users for web push", ["users" => array_map(function (Usr $user) {
            return $user->getUserid();
        }, [$recipient])]);

        return [$recipient];
    }

    /**
     * @return int[]
     */
    private function getUserIdsOnline(AbRequest $request)
    {
        $result = [];

        // TODO: move "do not send if user is online" condition to Sender ?
        $presence = $this->client->presence($this->bookingMessaging->getOnlineChannel($request));

        foreach ($presence[0]['body']['data'] as $item) {
            $result[] = (int) $item['user'];
        }

        return array_unique($result);
    }
}

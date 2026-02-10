<?php

namespace AwardWallet\MainBundle\Event\BookingMessage;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Event\BookingMessage;
use AwardWallet\MainBundle\Service\SocksMessaging\BookingMessaging;
use AwardWallet\MainBundle\Service\SocksMessaging\ClientInterface;
use Psr\Log\LoggerInterface;

class SocketListener
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
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ClientInterface $client,
        BookingMessaging $bookingMessaging,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->bookingMessaging = $bookingMessaging;
        $this->logger = $logger;
    }

    public function onBookingNewMessage(BookingMessage\NewEvent $event)
    {
        $abMessage = $event->getAbMessage();
        $abRequest = $abMessage->getRequest();
        $extras = $event->getExtras();

        $this->notifySocket($abRequest, array_merge(
            $this->getDefaulPayload($abMessage),
            [
                'action' => 'add',
                'notify' => true,
            ],
            (isset($extras['action']) && ('statusChange' === $extras['action'])) ?
                ['mobileDataReload' => true] :
                []
        ));
    }

    public function onBookingDeleteMessage(BookingMessage\DeleteEvent $event)
    {
        $abMessage = $event->getAbMessage();
        $abRequest = $abMessage->getRequest();

        $this->notifySocket($abRequest, array_merge(
            $this->getDefaulPayload($abMessage),
            [
                'action' => 'delete',
                'messageId' => $event->getMessageId(),
            ]
        ));
    }

    public function onBookingEditMessage(BookingMessage\EditEvent $event)
    {
        $abMessage = $event->getAbMessage();
        $abRequest = $abMessage->getRequest();

        $this->notifySocket($abRequest, array_merge(
            $this->getDefaulPayload($abMessage),
            ['action' => 'edit'],
            (isset($extras['action']) && ('statusChange' === $extras['action'])) ?
                ['mobileDataReload' => true] :
                []
        ));
    }

    private function notifySocket(AbRequest $abRequest, array $data)
    {
        foreach ([BookingMessaging::CHANNEL_MESSAGES, BookingMessaging::CHANNEL_USER_MESSAGES] as $channelCode) {
            if ($channelCode === BookingMessaging::CHANNEL_USER_MESSAGES) {
                if (!$abRequest->getUser()) {
                    continue;
                }
                $channelName = $this->bookingMessaging->getUserMessagesChannel($abRequest->getUser());
            } else {
                $channelName = $this->bookingMessaging->getMessagesChannel($abRequest);
            }

            $this->client->publish($channelName, $data);
        }
    }

    /**
     * @return array
     */
    private function getDefaulPayload(AbMessage $abMessage)
    {
        return [
            'needUpdate' => true,
            'internal' => $abMessage->isInternal(),
            'messageId' => $abMessage->getAbMessageID(),
            'requestId' => $abMessage->getRequest()->getAbRequestID(),
            'uid' => $abMessage->getUserID()->getUserid(),
        ];
    }
}

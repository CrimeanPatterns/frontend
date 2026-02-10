<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\Platform;

use Apns;
use GuzzleHttp\Psr7\Response;
use RMS\PushNotificationsBundle\Message\MessageInterface;
use RMS\PushNotificationsBundle\Service\OS\OSNotificationServiceInterface;

class IosSender implements OSNotificationServiceInterface
{
    /**
     * @var Apns\Client
     */
    protected $client;
    /**
     * @var Response
     */
    protected $response;

    /**
     * @var APNSHandler
     */
    protected $connectionHandler;

    public function __construct(
        string $awardwalletTeamPemPath,
        string $awardwalletTeamPassphrase,
        bool $isSandbox
    ) {
        $this->connectionHandler = new APNSHandler();
        $this->client = new Apns\Client(
            [$awardwalletTeamPemPath, $awardwalletTeamPassphrase],
            $isSandbox,
            $this->connectionHandler
        );
    }

    public function send(MessageInterface $pushBundleMessage)
    {
        if (!$pushBundleMessage instanceof iOSMessage) {
            throw new \LogicException(sprintf('Expects %s message class', iOSMessage::class));
        }

        try {
            $this->client->send($pushBundleMessage->getInnerMessage());
            $this->response = true;
        } catch (Apns\Exception\ApnsException $apnsException) {
            $this->response = $apnsException;
        }
    }

    public function getResponses()
    {
        if (!$this->response) {
            throw new \RuntimeException('Missing APNS response');
        }

        return [$this->response];
    }

    public function reconnect(): void
    {
        $this->connectionHandler->reconnect();
    }
}

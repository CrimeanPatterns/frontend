<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\Platform;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use RMS\PushNotificationsBundle\Message\MessageInterface;
use RMS\PushNotificationsBundle\Service\OS\OSNotificationServiceInterface;

class PushySender implements OSNotificationServiceInterface
{
    /**
     * @var Client
     */
    private $guzzleClient;
    /**
     * @var string
     */
    private $apiKey;
    /**
     * @var string
     */
    private $apiUrl;
    /**
     * @var array
     */
    private $responses = [];

    public function __construct(
        Client $guzzleClient,
        string $apiKey
    ) {
        $this->guzzleClient = $guzzleClient;
        $this->apiKey = $apiKey;
    }

    public function send(MessageInterface $message)
    {
        try {
            $response = $this->guzzleClient->post(
                $this->getApiUrl(),
                [
                    'json' => $message->getMessageBody(),
                ]
            );
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
        }

        $this->responses = [$response];
    }

    public function getResponses(): array
    {
        return $this->responses;
    }

    protected function getApiUrl(): string
    {
        if (!isset($this->apiUrl)) {
            $this->apiUrl = "https://api.pushy.me/push?api_key={$this->apiKey}";
        }

        return $this->apiUrl;
    }
}

<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\Platform;

use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\Messaging\ServerUnavailable;
use Minishlink\WebPush\Encryption;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\Utils;
use Minishlink\WebPush\WebPush;
use Psr\Log\LoggerInterface;
use RMS\PushNotificationsBundle\Message\MessageInterface;
use RMS\PushNotificationsBundle\Service\OS\OSNotificationServiceInterface;

class ChromeSender implements OSNotificationServiceInterface
{
    /**
     * @var string
     */
    protected $vapidPublicKey;
    /**
     * @var string
     */
    protected $vapidPrivateKey;
    /**
     * @var string
     */
    protected $gcmKey;
    private WebPush $defaultPusher;
    private WebPush $mozillaPusher;

    private $responses;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(string $vapidPublicKey, string $vapidPrivateKey, string $gcmKey, LoggerInterface $logger)
    {
        $this->defaultPusher = new WebPush();
        $this->mozillaPusher = new WebPush();
        $this->mozillaPusher->setAutomaticPadding(0);
        $this->logger = $logger;
        $this->gcmKey = $gcmKey;
        $this->vapidPublicKey = $vapidPublicKey;
        $this->vapidPrivateKey = $vapidPrivateKey;
    }

    /**
     * Send a notification message.
     *
     * @return void
     */
    public function send(MessageInterface $message)
    {
        $body = $message->getMessageBody();

        if (!isset($body['endpoint']) || !isset($body['payload']) || !isset($body['key']) || !isset($body['token'])) {
            $this->logger->critical("invalid message", ["body" => $body]);
            $this->responses = [true];
        } else {
            $options = [];

            if (isset($body['ttl'])) {
                $options['TTL'] = $body['ttl'];
            }

            $auth = [
                'VAPID' => [
                    'publicKey' => $this->vapidPublicKey,
                    'privateKey' => $this->vapidPrivateKey,
                    'subject' => 'https://awardwallet.com/contact',
                ],
                'GCM' => $this->gcmKey,
            ];

            $payload = json_encode($body['payload']);

            if (\is_string($payload) && (Utils::safeStrlen($payload) > Encryption::MAX_PAYLOAD_LENGTH)) {
                $this->logger->warning("Invalid chrome payload", ['_aw_server_module' => 'chrome_sender', 'payload' => $payload]);

                return;
            }

            /*
             * @see https://github.com/web-push-libs/web-push-php/issues/108
             */
            $pusher = (false !== \strpos($body['endpoint'], 'mozilla.')) ?
                $this->mozillaPusher :
                $this->defaultPusher;

            $r = $pusher->sendOneNotification(
                Subscription::create([
                    'endpoint' => $body["endpoint"],
                    'publicKey' => $body['key'],
                    'authToken' => $body['token'],
                ]),
                $payload,
                $options,
                $auth
            );

            if ($r->isSuccess()) {
                $this->responses = [true];
            } elseif ($r->getResponse() && $r->getResponse()->getStatusCode() === 410) {
                $this->responses = [new NotFound($r->getReason())];
            } else {
                $this->responses = [new ServerUnavailable($r->getReason())];
            }
        }
    }

    public function getResponses()
    {
        return $this->responses;
    }
}

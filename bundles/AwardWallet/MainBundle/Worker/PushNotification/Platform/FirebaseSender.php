<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\Platform;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\Messaging\QuotaExceeded;
use Kreait\Firebase\Exception\Messaging\ServerError;
use Kreait\Firebase\Exception\Messaging\ServerUnavailable;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Psr\Log\LoggerInterface;
use RMS\PushNotificationsBundle\Exception\InvalidMessageTypeException;
use RMS\PushNotificationsBundle\Message\AndroidMessage;
use RMS\PushNotificationsBundle\Message\MessageInterface;
use RMS\PushNotificationsBundle\Service\OS\OSNotificationServiceInterface;

class FirebaseSender implements OSNotificationServiceInterface
{
    private LoggerInterface $logger;
    private Messaging $messaging;
    private array $responses;

    public function __construct(string $firebasePrivateKeyFile, LoggerInterface $logger)
    {
        $factory = (new Factory())
            ->withServiceAccount($firebasePrivateKeyFile)
            // ->withHttpDebugLogger($logger)
        ;

        // $info = $factory->getDebugInfo();

        $this->messaging = $factory->createMessaging();
        $this->logger = $logger;
    }

    public function send(MessageInterface $message)
    {
        if (!$message instanceof AndroidMessage) {
            throw new InvalidMessageTypeException(sprintf("Message type '%s' not supported by GCM", get_class($message)));
        }

        if (!$message->isGCM()) {
            throw new InvalidMessageTypeException("Non-GCM messages not supported by the Android GCM sender");
        }

        $fcmMessage = CloudMessage::withTarget('token', $message->getDeviceIdentifier())
            ->withNotification(Notification::create($message->getData()['title'], $message->getMessage())) // optional
            ->withData($this->filterData($message->getData()))
        ;

        if ($androidConfigOptions = $message->getGCMOptions()) {
            $fcmMessage = $fcmMessage->withAndroidConfig($androidConfigOptions);
        }

        try {
            $this->responses = [$this->messaging->send($fcmMessage)];
        } catch (NotFound $e) {
            $this->logger->debug("Device not found: " . $e->getMessage());
            $this->responses = [$e];
        } catch (QuotaExceeded $e) {
            $this->logger->debug("fcm quota exceeded: " . $e->getMessage());
            $this->responses = [$e];
        } catch (ServerUnavailable $e) {
            $this->logger->debug("server unavailable: " . $e->getMessage());
            $this->responses = [$e];
        } catch (ServerError $e) {
            $this->logger->debug("server error: " . $e->getMessage());
            $this->responses = [$e];
        }
    }

    /**
     * Returns responses.
     *
     * @return array
     */
    public function getResponses()
    {
        return $this->responses;
    }

    private function filterData(array $data): array
    {
        $filtered = [];
        $errors = false;

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $errors = true;

                continue;
            }

            $filtered[$key] = $value;
        }

        if ($errors) {
            $this->logger->error("invalid fcm data: " . json_encode($data));
        }

        return $filtered;
    }
}

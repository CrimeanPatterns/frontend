<?php

namespace AwardWallet\MainBundle\Worker\PushNotification;

use AwardWallet\MainBundle\Worker\PushNotification\DTO\DeviceAction;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Notification;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Psr\Log\LoggerInterface;

use function Duration\milliseconds;
use function Duration\seconds;

class NotificationHelper
{
    private const MAX_EXPONENTIAL_RETRY_NUMBER = 17; // ~36 hours
    private const BACKOFF_RANDOMIZATION_DIVISOR = 8;
    /**
     * @var ProducerInterface
     */
    private $producer;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var LogHelper
     */
    private $logHelper;
    /**
     * @var ProducerInterface
     */
    private $delayedProducer;

    public function __construct(
        ProducerInterface $producer,
        ProducerInterface $delayedProducer,
        LoggerInterface $logger,
        LogHelper $logHelper)
    {
        $this->producer = $producer;
        $this->delayedProducer = $delayedProducer;
        $this->logger = $logger;
        $this->logHelper = $logHelper;
    }

    /**
     * Retry after time delay. Delay bounded to logarithmic(base 2) scale.
     *
     * @param int $delay seconds
     */
    public function retryAfter($delay, Notification $notification)
    {
        $retriesPrev = $notification->getRetries();

        if (
            is_infinite($delay) || is_nan($delay)
            || ($delay < 0) || ($delay >= SECONDS_PER_HOUR)
        ) {
            $this->logger->error($text = sprintf('Malformed delay value: %d, reset to 2', $delay), $this->logHelper->getContext($notification, ['_aw_push_retries_exp' => $retriesPrev]));
            $delay = 32;
        } elseif ($delay >= 0 && $delay < 2) {
            $delay = 2;
        }

        $delay = (int) $delay;

        $retries = $retriesPrev > 0 ?
            (ceil(log(pow(2, $retriesPrev) + $delay, 2)) - $retriesPrev) :
            ceil(log($delay, 2));

        $notification->addRetries($retries);

        $this->_retry($notification);
    }

    /**
     * Add one exponential back-off retry.
     */
    public function retry(Notification $notification)
    {
        $notification->addRetries(1);
        $this->_retry($notification);
    }

    /**
     * @param int $action
     * @param mixed|null $data
     */
    public function deviceAction(Notification $notification, $action, $data = null)
    {
        $actionName = DeviceAction::getActionName($action);
        $this->logger->info(sprintf('Send device action "%s", deviceKey: %s',
            $actionName, $notification->getDeviceKey()
        ),
            $this->logHelper->getContext($notification, ['_aw_push_device_action' => $actionName])
        );
        $this->producer->publish(@serialize(new DeviceAction($notification, $action, $data)), 'device_2');
    }

    /**
     * @throws \UnexpectedValueException
     */
    private function _retry(Notification $notification)
    {
        $retryNumber = $notification->getRetries();

        if ($retryNumber > self::MAX_EXPONENTIAL_RETRY_NUMBER) {
            $this->logger->critical(sprintf('Retry limit exceeded, message: "%s"', $notification->getMessage()), $this->logHelper->getFailContext($notification));
        } else {
            $this->logger->warning('Retrying', $this->logHelper->getContext($notification, ['_aw_push_retries_exp' => $retryNumber]));
            $delay = seconds(2 ** $retryNumber);
            $delay = $delay->add(milliseconds(\random_int(0, $delay->getAsMillisecondsInt() / self::BACKOFF_RANDOMIZATION_DIVISOR)));
            $props = [
                'application_headers' => [
                    'x-delay' => [
                        'I', $delay->getAsMillisecondsInt(),
                    ],
                ],
            ];

            $priority = $notification->getOptions()->getPriority();

            if ($priority) {
                $props['priority'] = $priority;
            }

            $this->delayedProducer->publish(
                @serialize($notification),
                $notification->getRoutingKey(),
                $props
            );
        }
    }
}

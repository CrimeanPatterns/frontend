<?php

namespace AwardWallet\MainBundle\Service;

use OldSound\RabbitMqBundle\RabbitMq\Producer;

/**
 * @see https://github.com/oscherler/rabbitmq-delayed-sample
 */
class DelayedProducer
{
    protected $connection;
    protected $destination_exchange;
    protected $prefix;

    /**
     * @var Producer[]
     */
    protected $pool;

    public function __construct($connection, $destination_exchange, $prefix)
    {
        $this->connection = $connection;
        $this->destination_exchange = $destination_exchange;

        if (!is_string($prefix) || strlen($prefix) > 60) {
            throw new \UnexpectedValueException('Prefix should be a string of length <= 60.');
        }
        $this->prefix = $prefix;
    }

    /**
     * @param int $delay milliseconds
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public function delayedPublish($delay, $msgBody, $routingKey = '', $additionalProperties = [])
    {
        if (!is_integer($delay) || $delay < 0) {
            throw new \UnexpectedValueException("Publish delay should be a positive integer: \"$delay\".");
        }

        // expire the queue a little bit after the delay, but minimum 1 second
        $expiration = 1000 + floor(1.1 * $delay);

        $name = sprintf('%s-delayed', $this->prefix);
        $id = sprintf('%s-waiting-queue-%s-%d', $this->prefix, $routingKey, $delay);

        $poolKey = $name . '_' . $id;

        if (isset($this->pool[$poolKey])) {
            $producer = $this->pool[$poolKey];
        } else {
            $producer = new Producer($this->connection);
            $this->pool[$poolKey] = $producer;

            $producer->setExchangeOptions([
                'name' => $name,
                'type' => 'direct',
            ]);
            $producer->setQueueOptions([
                'name' => $id,
                'routing_keys' => [$id],
                'arguments' => [
                    'x-message-ttl' => ['I', $delay],
                    'x-dead-letter-exchange' => ['S', $this->destination_exchange],
                    'x-dead-letter-routing-key' => ['S', $routingKey],
                    'x-expires' => ['I', $expiration],
                ],
            ]);
            $producer->setupFabric();
        }

        $producer->publish($msgBody, $id, $additionalProperties);
    }
}

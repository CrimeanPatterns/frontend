<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use PhpAmqpLib\Channel\AMQPChannel;

class TestAMQPChannel extends AMQPChannel
{
    /**
     * @throws \Exception
     */
    public function __construct()
    {
    }

    /**
     * Declares exchange.
     *
     * @param string $exchange
     * @param string $type
     * @param bool $passive
     * @param bool $durable
     * @param bool $auto_delete
     * @param bool $internal
     * @param bool $nowait
     * @return mixed|null
     */
    public function exchange_declare(
        $exchange,
        $type,
        $passive = false,
        $durable = false,
        $auto_delete = true,
        $internal = false,
        $nowait = false,
        $arguments = null,
        $ticket = null
    ) {
        return null;
    }

    /**
     * Binds queue to an exchange.
     *
     * @param string $queue
     * @param string $exchange
     * @param string $routing_key
     * @param bool $nowait
     * @param null $arguments
     * @param null $ticket
     * @return mixed|null
     */
    public function queue_bind($queue, $exchange, $routing_key = '', $nowait = false, $arguments = null, $ticket = null)
    {
        return null;
    }

    /**
     * Declares queue, creates if needed.
     *
     * @param string $queue
     * @param bool $passive
     * @param bool $durable
     * @param bool $exclusive
     * @param bool $auto_delete
     * @param bool $nowait
     * @param null $arguments
     * @param null $ticket
     * @return mixed|null
     */
    public function queue_declare(
        $queue = '',
        $passive = false,
        $durable = false,
        $exclusive = false,
        $auto_delete = true,
        $nowait = false,
        $arguments = null,
        $ticket = null
    ) {
        return null;
    }

    /**
     * Publishes a message.
     *
     * @param AMQPMessage $msg
     * @param string $exchange
     * @param string $routing_key
     * @param bool $mandatory
     * @param bool $immediate
     * @param null $ticket
     */
    public function basic_publish(
        $msg,
        $exchange = '',
        $routing_key = '',
        $mandatory = false,
        $immediate = false,
        $ticket = null
    ) {
        return null;
    }
}

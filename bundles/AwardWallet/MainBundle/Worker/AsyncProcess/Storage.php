<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use AwardWallet\Common\DateTimeUtils;
use Opis\Closure\SerializableClosure;

class Storage
{
    public const CACHE_PREFIX = "aw_async_process_";
    /**
     * @var \Memcached
     */
    private $memcached;

    public function __construct(\Memcached $memcached, $secretKey)
    {
        $this->memcached = $memcached;
        SerializableClosure::setSecretKey($secretKey);
    }

    /**
     * @return Response
     */
    public function getResponse(Task $task)
    {
        $result = $this->memcached->get(self::CACHE_PREFIX . $task->requestId);

        if (empty($result)) {
            $result = new Response();
        }

        return $result;
    }

    public function setResponse(Task $task, Response $response)
    {
        $this->memcached->set(self::CACHE_PREFIX . $task->requestId, $response, DateTimeUtils::SECONDS_PER_DAY);
    }
}

<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Engine;

use AwardWallet\MainBundle\Service\FlightInfo\Exceptions\HttpRequestException;
use Psr\Log\LoggerInterface;

class LockableEngine extends Engine
{
    protected MemcacheLocker $locker;

    public function __construct($request_timeout, MemcacheLocker $locker, LoggerInterface $logger, LoggerInterface $statLogger)
    {
        parent::__construct($request_timeout, $logger, $statLogger);

        $this->locker = $locker;
    }

    public function send(HttpRequest $request)
    {
        if ($this->locker->locked()) {
            throw new HttpRequestException('in lockout');
        }

        $curl = curl_init();

        try {
            $response = $this->exec($curl, $request);

            if ($response === false) {
                $this->logger->error("[FlightInfo] request error: " . curl_error($curl),
                    ['request' => $request->getDescription()]);
                $this->locker->failure();

                if ($this->locker->locked()) {
                    $this->logger->error("[FlightInfo] requests lockout");
                }

                throw new HttpRequestException();
            }

            $ret = $this->process($curl, $response);
        } finally {
            curl_close($curl);
        }

        return $ret;
    }
}

<?php

namespace AwardWallet\MainBundle\Service;

use Doctrine\DBAL\Exception\RetryableException;
use Psr\Log\LoggerInterface;

class DoctrineRetryHelper
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(callable $code)
    {
        for ($try = 0; $try < 3; $try++) {
            try {
                return $code();
            } catch (RetryableException $e) {
                $lastException = $e;
                $this->logger->notice('DoctrineRetryHelper: ' . $e->getMessage());
                sleep(random_int(1, 6));
            }
        }

        throw $lastException;
    }
}

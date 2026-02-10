<?php

namespace AwardWallet\MainBundle\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class PasswordLeakChecker
{
    private LoggerInterface $logger;
    /**
     * @var \Doctrine\DBAL\Statement
     */
    private $query;

    public function __construct(Connection $vendorConnection, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->query = $vendorConnection->prepare("select LeakCount from PasswordHash where Hash =  ?");
    }

    /**
     * @return int - leak count
     */
    public function checkPassword(string $password): int
    {
        $hash = sha1($password);
        $this->query->execute([$hash]);

        return (int) $this->query->fetchColumn();
    }
}

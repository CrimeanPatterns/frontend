<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Psr\Log\LoggerInterface;

class ClickhouseFactory
{
    private ParameterRepository $paramRepository;

    private LoggerInterface $logger;

    private string $host;

    private Configuration $configuration;

    public function __construct(
        ParameterRepository $paramRepository,
        LoggerInterface $logger,
        string $clickhouseHost,
        Configuration $doctrineDefaultConfiguration
    ) {
        $this->paramRepository = $paramRepository;
        $this->logger = $logger;
        $this->host = $clickhouseHost;
        $this->configuration = $doctrineDefaultConfiguration;
    }

    public function getConnection(): Connection
    {
        return DriverManager::getConnection(
            [
                'host' => $this->host,
                'port' => 9004,
                'user' => 'default',
                'dbname' => $this->getDatabaseName(),
                'password' => '',
                'driverClass' => \Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Driver\PDOMySql\Driver::class,
                'wrapperClass' => \Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Connection::class,
                'driverOptions' => [
                    'x_reconnect_attempts' => 2,
                    1000 => false, // MYSQL_ATTR_USE_BUFFERED_QUERY
                ],
            ],
            $this->configuration
        );
    }

    private function getDatabaseName(): string
    {
        $dbVersion = sprintf("awardwallet_v%s", $this->paramRepository->getParam(ParameterRepository::CLICKHOUSE_DB_VERSION));
        $this->logger->info("Current ClickHouse DB: " . $dbVersion);

        return $dbVersion;
    }
}

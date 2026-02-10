<?php

namespace AwardWallet\MainBundle\Globals;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Connection;
use Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Driver\PDOMySql\Driver;

class UnbufferedConnectionFactory
{
    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;
    /**
     * @var string
     */
    private $defaultDbHost;
    /**
     * @var string|null
     */
    private $defaultDbPort;
    /**
     * @var string
     */
    private $defaultDbName;
    /**
     * @var string
     */
    private $defaultDbUser;
    /**
     * @var string
     */
    private $defaultDbPassword;
    /**
     * @var string
     */
    private $archiveDbHost;
    /**
     * @var string|null
     */
    private $archiveDbPort;
    /**
     * @var string
     */
    private $archiveDbName;
    /**
     * @var string
     */
    private $archiveDbUser;
    /**
     * @var string
     */
    private $archiveDbPassword;

    public function __construct(
        ConnectionFactory $connectionFactory,
        // default
        string $defaultDbHost,
        ?string $defaultDbPort,
        string $defaultDbName,
        string $defaultDbUser,
        string $defaultDbPassword,
        // archive
        string $archiveDbHost,
        ?string $archiveDbPort,
        string $archiveDbName,
        string $archiveDbUser,
        string $archiveDbPassword
    ) {
        $this->connectionFactory = $connectionFactory;
        $this->defaultDbHost = $defaultDbHost;
        $this->defaultDbPort = $defaultDbPort;
        $this->defaultDbName = $defaultDbName;
        $this->defaultDbUser = $defaultDbUser;
        $this->defaultDbPassword = $defaultDbPassword;

        $this->archiveDbHost = $archiveDbHost;
        $this->archiveDbPort = $archiveDbPort;
        $this->archiveDbName = $archiveDbName;
        $this->archiveDbUser = $archiveDbUser;
        $this->archiveDbPassword = $archiveDbPassword;
    }

    public function createConnection(?string $host = null): \Doctrine\DBAL\Connection
    {
        return $this->createUnbufferedConnection(
            $host ?? $this->defaultDbHost,
            $this->defaultDbPort,
            $this->defaultDbName,
            $this->defaultDbUser,
            $this->defaultDbPassword
        );
    }

    public function createArchiveConnection(?string $host = null): \Doctrine\DBAL\Connection
    {
        return $this->createUnbufferedConnection(
            $host ?? $this->archiveDbHost,
            $this->archiveDbPort,
            $this->archiveDbName,
            $this->archiveDbUser,
            $this->archiveDbPassword
        );
    }

    private function createUnbufferedConnection(string $host, ?string $port, string $dbName, string $user, string $password): \Doctrine\DBAL\Connection
    {
        return $this->connectionFactory->createConnection(
            [
                'driver' => 'pdo_mysql',
                'host' => $host,
                'port' => $port,
                'dbname' => $dbName,
                'user' => $user,
                'password' => $password,
                'charset' => 'UTF8',
                'driverOptions' => ['x_reconnect_attempts' => 2, 1000 => false],
                'driverClass' => Driver::class,
                'wrapperClass' => Connection::class,
            ],
            null,
            null,
            ['ab_message_metadata' => 'string']
        );
    }
}

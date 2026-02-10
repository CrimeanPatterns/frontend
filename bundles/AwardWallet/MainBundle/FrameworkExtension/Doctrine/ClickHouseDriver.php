<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Doctrine;

use FOD\DBALClickHouse\ClickHouseException;
use FOD\DBALClickHouse\Driver;

class ClickHouseDriver extends Driver
{
    public function connect(array $params, $user = null, $password = null, array $driverOptions = []): ClickHouseConnection
    {
        if ($user === null) {
            if (!isset($params['user'])) {
                throw new ClickHouseException('Connection parameter `user` is required');
            }

            $user = $params['user'];
        }

        if ($password === null) {
            if (!isset($params['password'])) {
                throw new ClickHouseException('Connection parameter `password` is required');
            }

            $password = $params['password'];
        }

        if (!isset($params['host'])) {
            throw new ClickHouseException('Connection parameter `host` is required');
        }

        if (!isset($params['port'])) {
            throw new ClickHouseException('Connection parameter `port` is required');
        }

        if (!isset($params['dbname'])) {
            throw new ClickHouseException('Connection parameter `dbname` is required');
        }

        return new ClickHouseConnection($params, (string) $user, (string) $password, $this->getDatabasePlatform());
    }
}

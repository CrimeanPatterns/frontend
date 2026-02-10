<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;

class ClickHouseConnection extends \FOD\DBALClickHouse\ClickHouseConnection
{
    /**
     * Connection constructor.
     *
     * @param mixed[] $params
     */
    public function __construct(
        array $params,
        string $username,
        string $password,
        AbstractPlatform $platform
    ) {
        parent::__construct($params, $username, $password, $platform);
        $this->smi2CHClient->setTimeout(500);
        $this->smi2CHClient->setConnectTimeOut(500);
    }
}

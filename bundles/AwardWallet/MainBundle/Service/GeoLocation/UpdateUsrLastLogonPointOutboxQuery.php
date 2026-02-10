<?php

namespace AwardWallet\MainBundle\Service\GeoLocation;

use AwardWallet\MainBundle\Entity\Outbox;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class UpdateUsrLastLogonPointOutboxQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function execute(int $userId, ?float $lat, ?float $lng): void
    {
        $this->connection->executeStatement(
            "INSERT INTO Outbox (TypeID, Payload)
                VALUES(:type, JSON_OBJECT('UserID', :user, 'Lat', :lat, 'Lng', :lng))",
            [
                'type' => Outbox::TYPE_USR_LAST_LOGON_POINT,
                'user' => $userId,
                'lat' => $lat,
                'lng' => $lng,
            ],
            [
                'type' => ParameterType::INTEGER,
                'user' => ParameterType::INTEGER,
                'lat' => ParameterType::STRING,
                'lng' => ParameterType::STRING,
            ]
        );
    }
}

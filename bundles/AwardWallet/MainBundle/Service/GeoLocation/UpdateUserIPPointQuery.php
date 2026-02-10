<?php

namespace AwardWallet\MainBundle\Service\GeoLocation;

use AwardWallet\MainBundle\Entity\Outbox;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class UpdateUserIPPointQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function execute(int $userId, string $ip, ?float $lat, ?float $lng)
    {
        $this->connection->executeStatement("
            INSERT INTO UserIP(UserID, IP) 
            VALUES(:user, :ip) 
            ON DUPLICATE KEY UPDATE 
                UpdateDate = now(),
                UserIPID = LAST_INSERT_ID(UserIPID)",
            [
                'user' => $userId,
                'ip' => $ip,
            ],
        );
        $this->connection->executeStatement("
                INSERT INTO Outbox (TypeID, Payload)
                VALUES(:type, JSON_OBJECT('UserIPID', LAST_INSERT_ID(), 'Lat', :lat, 'Lng', :lng))",
            [
                'type' => Outbox::TYPE_USERIP_POINT,
                'lat' => $lat,
                'lng' => $lng,
            ],
            [
                'type' => ParameterType::INTEGER,
                'lat' => ParameterType::STRING,
                'lng' => ParameterType::STRING,
            ]
        );
    }
}

<?php

namespace AwardWallet\MainBundle\Service\GeoLocation\PointOutboxProcessor;

use Doctrine\DBAL\Connection;

class PointProcessor
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function process(array $outboxItem, string $toTable, string $pkColumn): void
    {
        $version = $outboxItem['OutboxID'];
        ['Lat' => $lat, 'Lng' => $lng, $pkColumn => $pkId] = \json_decode($outboxItem['Payload'], true);
        $this->connection->executeStatement("
            insert into {$toTable} (`{$pkColumn}`, `Point`, IsSet, Version) 
            values (
                :pk, 
                ST_SRID(
                    Point(
                        ifnull(:lng, RAND() * 360.0 - 180.0), 
                        ifnull(:lat, RAND() * 180.0 - 90.0)
                    ), 
                    4326
                ), 
                :isset, 
                :version
            )
            on duplicate key update 
                `Point` = IF(Version < VALUES(Version), VALUES(`Point`), `Point`),
                IsSet = IF(Version < VALUES(Version), VALUES(IsSet), IsSet),
                Version = IF(Version < VALUES(Version), VALUES(Version), Version)",
            [
                'pk' => $pkId,
                'lat' => $lat,
                'lng' => $lng,
                'version' => $version,
                'isset' => (int) !($lat === null || $lng === null),
            ]
        );
    }
}

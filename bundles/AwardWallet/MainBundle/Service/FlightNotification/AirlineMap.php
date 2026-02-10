<?php

namespace AwardWallet\MainBundle\Service\FlightNotification;

use AwardWallet\MainBundle\Globals\Utils\LazyVal;
use Doctrine\DBAL\Connection;

use function AwardWallet\MainBundle\Globals\Utils\lazy;

class AirlineMap
{
    private ?LazyVal $map;

    public function __construct(Connection $connection)
    {
        $this->map = lazy(function () use ($connection) {
            $map = [];
            $stmt = $connection->executeQuery("
                SELECT 
                   IATACode,
                   ProviderID
                FROM Provider
                WHERE IATACode IS NOT NULL
            ");

            while ($row = $stmt->fetchAssociative()) {
                $iataCode = mb_strtoupper($row['IATACode']);

                if (!isset($map[$iataCode])) {
                    $map[$iataCode] = [];
                }
                $map[$iataCode][] = (int) $row['ProviderID'];
            }

            return $map;
        });
    }

    public function get(string $iataCode): ?array
    {
        return $this->map[$iataCode] ?? null;
    }
}

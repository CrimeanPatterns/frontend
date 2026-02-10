<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class FareClassMapper
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return string - one of Constants::CLASS_
     */
    public function map(int $airlineId, string $fareClass): ?string
    {
        static $map;

        if ($map === null) {
            $map = it($this->connection->executeQuery("
                select 
                    concat(AirlineID, '-', FareClass) as Hash, ClassOfService as Value 
                from AirlineFareClass")->fetchAll(FetchMode::ASSOCIATIVE))
                ->flatMap(function (array $row) {
                    return [$row["Hash"] => $row["Value"]];
                })
                ->toArrayWithKeys();
        }

        return $map[$airlineId . '-' . $fareClass] ?? $map[$airlineId . '-*'] ?? null;
    }
}

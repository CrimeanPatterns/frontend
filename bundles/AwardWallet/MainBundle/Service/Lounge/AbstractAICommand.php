<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;

abstract class AbstractAICommand extends Command
{
    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();

        $this->connection = $connection;
    }

    /**
     * @return string[]
     */
    protected function getPriorityAircodes(int $top = 100): array
    {
        $aircodes = $this->connection->fetchFirstColumn('
            SELECT DISTINCT AirportCode 
            FROM LoungeSource
            ORDER BY AirportCode
        ');

        return $this->connection->fetchFirstColumn(
            "
                SELECT AirCode 
                FROM AirCode 
                WHERE AirCode IN (?) 
                ORDER BY Popularity DESC LIMIT $top
            ", [$aircodes], [Connection::PARAM_STR_ARRAY]
        );
    }
}

<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class CabinClassMapper
{
    private Connection $connection;
    private LoggerInterface $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function cabinClassToClassOfService(string $cabinClass, ?string $operatingAirline, int $tripId): ?string
    {
        if (empty($cabinClass) && in_array($operatingAirline, Constants::ASSUME_BASIC_ECONOMY)) {
            return Constants::CLASS_BASIC_ECONOMY;
        }

        $classKey = $operatingAirline . ':' . $cabinClass;

        $result = $this->connection->executeQuery("select Target from AirClassDictionary 
        where Source = ? and (AirlineCode = ? or AirlineCode is null) order by if(AirlineCode is null, 1, 0)", [$cabinClass, $operatingAirline])->fetchColumn();

        if ($result === false || $result === Constants::CLASS_MAP_UNKNOWN || $result === Constants::CLASS_MAP_PARSE_ERROR) {
            $this->logger->warning("Unknown cabin class: " . $classKey);

            if ($result === false) {
                $this->connection->executeStatement(
                    "insert into AirClassDictionary(Source, AirlineCode, Target, TripIDs)
                    values(?, ?, 'Unknown', ?)
                    on duplicate key update TripIDs = concat(TripIDs, ', ', values(TripIDs))",
                    [$cabinClass, $operatingAirline, $tripId]
                );
                $this->logger->info("added $classKey to dictionary");
            }

            $result = null;
        }

        return $result;
    }
}

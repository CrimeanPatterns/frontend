<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Entity\Lounge;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;

class Finder
{
    private Connection $connection;

    private EntityRepository $repository;

    public function __construct(Connection $connection, EntityRepository $loungeRepository)
    {
        $this->connection = $connection;
        $this->repository = $loungeRepository;
    }

    public function getNumberAirportLounges(string $airportCode): int
    {
        return $this->connection->executeQuery('
            SELECT COUNT(*)
            FROM Lounge
            WHERE
                AirportCode = ?
                AND IsAvailable = 1
                AND Visible = 1
        ', [$airportCode], [\PDO::PARAM_STR])->fetchOne();
    }

    /**
     * @return Lounge[]
     */
    public function getLounges(string $airportCode): array
    {
        return $this->repository->findBy([
            'airportCode' => $airportCode,
            'isAvailable' => true,
            'visible' => true,
        ]);
    }
}

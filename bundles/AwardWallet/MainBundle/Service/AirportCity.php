<?php

namespace AwardWallet\MainBundle\Service;

use Doctrine\DBAL\Connection;

class AirportCity
{
    /**
     * @var \Doctrine\DBAL\Statement
     */
    private $cityQuery;

    private $cityCache = [];

    public function __construct(
        Connection $connection
    ) {
        $this->cityQuery = $connection->prepare("select CityCode from AirCode where AirCode = ?");
    }

    public function findCity(string $airCode): string
    {
        $result = $this->cityCache[$airCode] ?? null;

        if ($result !== null) {
            return $result;
        }

        $this->cityQuery->execute([$airCode]);
        $result = $this->cityQuery->fetchColumn();
        $this->cityCache[$airCode] = $result;

        return $result;
    }
}

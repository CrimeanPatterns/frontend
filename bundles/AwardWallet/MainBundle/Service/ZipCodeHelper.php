<?php

namespace AwardWallet\MainBundle\Service;

use Doctrine\DBAL\Connection;

class ZipCodeHelper
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findLocationByZipCode(string $zip): array
    {
        $row = $this->connection
                    ->executeQuery("SELECT * FROM ZipCode WHERE Zip = ?", [substr(trim($zip), 0, 5)])
                    ->fetch();

        if (!$row) {
            return [null, null];
        }

        return [$row['Lat'], $row['Lng']];
    }
}

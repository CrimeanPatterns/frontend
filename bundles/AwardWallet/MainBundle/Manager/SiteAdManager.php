<?php

namespace AwardWallet\MainBundle\Manager;

use Doctrine\DBAL\Connection;

class SiteAdManager
{
    /**
     * @var Connection
     */
    private $dbConnection;

    public function __construct(Connection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    public function updateClicksForRef(int $ref): void
    {
        $this->updateCounterForRef('Clicks', 'LastClick', $ref);
    }

    public function updateAppInstallsForRef(int $ref): void
    {
        $this->updateCounterForRef('AppInstalls', 'LastAppInstall', $ref);
    }

    private function updateCounterForRef(string $counterField, string $lastDateField, int $ref): void
    {
        $this->dbConnection->executeQuery("
            UPDATE SiteAd
            SET {$lastDateField} = NOW(), {$counterField} = IFNULL({$counterField}, 0) + 1
            WHERE SiteAdID = " . ((int) $ref) . '
            LIMIT 1
        ');
    }
}

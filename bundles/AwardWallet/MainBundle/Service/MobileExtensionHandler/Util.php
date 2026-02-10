<?php

namespace AwardWallet\MainBundle\Service\MobileExtensionHandler;

use Doctrine\DBAL\Connection;

class Util
{
    private Connection $dbConnection;

    public function __construct(Connection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    public function getAnyAccount(int $userId, int $providerId, ?string $names = null): ?int
    {
        $accounts = $this->dbConnection->executeQuery("
            SELECT	
                a.AccountID,
                a.ProviderID,
                a.UserID,
                a.UserAgentID,
                coalesce (ua.FirstName, u.FirstName) AS FirstName,
                coalesce (ua.LastName, u.LastName)   AS LastName
            FROM   	
                Account a
                JOIN Usr u ON a.UserID = u.UserID
                LEFT JOIN UserAgent ua ON a.UserAgentID = ua.UserAgentID
            WHERE  	
                a.UserID = :userId
                AND a.ProviderID = :providerId
            ORDER BY 
                a.UserAgentID IS NOT NULL",
            ["userId" => $userId, "providerId" => $providerId]
        )->fetchAll(\PDO::FETCH_ASSOC);

        $id = null;

        if (!empty($accounts)) {
            // first one - default, if no match or no names in itineraries
            $id = (int) $accounts[0]["AccountID"];

            if (!empty($names)) {
                $names = explode(",", $names);

                foreach ($accounts as $row) {
                    foreach ($names as $name) {
                        if (stripos($name, $row["FirstName"]) !== false
                            && stripos($name, $row["LastName"]) !== false) {
                            $id = (int) $row["AccountID"];

                            break 2;
                        }
                    }
                }
            }
        }

        return $id;
    }
}

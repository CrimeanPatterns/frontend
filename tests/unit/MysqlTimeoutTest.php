<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Globals\StringHandler;

/**
 * @group frontend-unit
 */
class MysqlTimeoutTest extends BaseContainerTest
{
    public function testTimeout()
    {
        $conn = $this->em->getConnection();
        $conn->exec("set wait_timeout = 1");
        $conn->exec("set interactive_timeout = 1");
        $conn->executeQuery("select AccountID from Account limit 1")->fetchColumn(0);
        sleep(2);
        $accountId = $conn->executeQuery("select AccountID from Account limit 1")->fetchColumn(0);
        $this->assertGreaterThan(0, $accountId);
    }

    public function testUpdate()
    {
        $conn = $this->em->getConnection();
        $conn->exec("set wait_timeout = 1");
        $conn->exec("set interactive_timeout = 1");
        $paramName = "S." . StringHandler::getRandomCode(8);
        $conn->executeUpdate("delete from DiffChange where SourceID = ?", [$paramName]);
        $query = $conn->prepare("
            INSERT INTO DiffChange(SourceID, Property, OldVal, NewVal, ChangeDate, ExpirationDate)
            VALUES(?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE NewVal = ?
        ");
        $query->execute([$paramName, "Aircraft", "738", "Boeing 737-800 (winglets) Passenger\/BBJ2", "2017-02-10 02:13:38", "2017-03-14 18:58:00", "Boeing 737-800 (winglets) Passenger\/BBJ2"]);
        sleep(2);
        $query->execute([$paramName, "Aircraft", "738", "Boeing 737-800 (winglets) Passenger\/BBJ2", "2017-02-10 02:13:38", "2017-03-14 18:58:00", "Boeing 737-800 (winglets) Passenger\/BBJ2"]);
        $conn->executeUpdate("delete from DiffChange where SourceID = ?", [$paramName]);
    }
}

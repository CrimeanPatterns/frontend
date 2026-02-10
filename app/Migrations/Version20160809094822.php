<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Manager\BookingRequestManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160809094822 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var BookingRequestManager
     */
    private $requestManager;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->requestManager = $container->get('aw.manager.abrequest_manager');
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbRequestMark` COMMENT 'Записи о последней дате прочтения реквеста. Если нет строчки в этой таблице, то реквест считается прочитанным.';");

        $this->connection->transactional(function () {
            $stmt = $this->connection->executeQuery("
                SELECT *
                FROM AbRequestMark
            ");

            $values = [];

            while ($mark = $stmt->fetch()) {
                if ($mark['IsRead']) {
                    // set greater than or equal last message date
                    $maxDate = $this->connection->executeQuery("
                        SELECT 
                              MAX(abm.CreateDate) as maxdate
                        FROM AbMessage abm
                        WHERE abm.RequestID = {$mark['RequestID']}"
                    )->fetchAll()[0]['maxdate'];

                    if (!empty($maxDate)) {
                        $values[] = "({$mark['UserID']}, '{$maxDate}', {$mark['RequestID']})";
                    }
                } else {
                    // set greater than or equal last user message date
                    $maxDate = $this->connection->executeQuery("
                        SELECT 
                            MAX(abm.CreateDate) as maxdate
                        FROM AbMessage abm
                        WHERE abm.RequestID = {$mark['RequestID']} AND abm.UserID = {$mark['UserID']}"
                    )->fetchAll()[0]['maxdate'];

                    $maxDate = max($maxDate, $mark['ReadDate']);

                    $values[] = "({$mark['UserID']}, '{$maxDate}', {$mark['RequestID']})";
                }

                if (count($values) === 1000) {
                    $this->updateNoRead($values);
                    $values = [];
                }
            }

            if ($values) {
                $this->updateNoRead($values);
                $values = [];
            }
        });
    }

    public function down(Schema $schema): void
    {
        $this->connection->transactional(function () {
            $stmt = $this->connection->executeQuery("
                SELECT *
                FROM AbRequestMark
            ");

            $values = [];

            while ($mark = $stmt->fetch()) {
                $maxDates = $this->connection->executeQuery("
                    SELECT 
                          MAX(IF(abm.UserID <> {$mark['UserID']}, abm.CreateDate, abr.CreateDate)) as maxdate_by_others,
                          MAX(IF(abm.UserID = {$mark['UserID']}, abm.CreateDate, abr.CreateDate)) as maxdate_by_user
                    FROM AbMessage abm
                    JOIN AbRequest abr ON abr.AbRequestID = abm.RequestID
                    WHERE abm.RequestID = {$mark['RequestID']}"
                )->fetchAll()[0];

                $isRead = (int) ($maxDates['maxdate_by_others'] <= $maxDates['maxdate_by_user']);
                $date = max($maxDates['maxdate_by_user'], $maxDates['maxdate_by_user']);

                $values[] = "({$mark['UserID']}, '{$date}', {$mark['RequestID']}, {$isRead})";

                if (count($values) === 1000) {
                    $this->updateRead($values);
                    $values = [];
                }
            }

            if ($values) {
                $this->updateRead($values);
                $values = [];
            }
        });
    }

    private function updateNoRead(array $values)
    {
        $values = implode(', ', $values);
        $this->connection->executeQuery('INSERT INTO AbRequestMark (UserID, ReadDate, RequestID) VALUES ' . $values . ' ON DUPLICATE KEY UPDATE ReadDate = IF(ReadDate < VALUES(ReadDate), VALUES(ReadDate), ReadDate)');
    }

    private function updateRead(array $values)
    {
        $values = implode(', ', $values);
        $this->connection->executeQuery('INSERT INTO AbRequestMark (UserID, ReadDate, RequestID, IsRead) VALUES ' . $values . ' ON DUPLICATE KEY UPDATE ReadDate = IF(ReadDate < VALUES(ReadDate), VALUES(ReadDate), ReadDate), IsRead = VALUES(IsRead)');
    }
}

<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160227092421 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->connection->exec("
            CREATE TABLE `AbInvoiceItem` (
              `AbInvoiceItemID` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `Description` varchar(255) NOT NULL COMMENT 'Название поля',
              `Quantity` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'Кол-во',
              `Price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT 'Стоимость',
              `Discount` tinyint(3) unsigned NULL DEFAULT NULL COMMENT 'Скидка',
              `AbInvoiceID` int(11) NOT NULL COMMENT 'Ссылка на инвойс',
              PRIMARY KEY (`AbInvoiceItemID`),
              KEY `AbInvoiceID_FK` (`AbInvoiceID`),
              CONSTRAINT `AbInvoiceID_FK` FOREIGN KEY (`AbInvoiceID`) REFERENCES `AbInvoice` (`AbInvoiceID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Поля в счете';
        ");

        $stm = $this->connection->prepare("INSERT INTO AbInvoiceItem (Description, Quantity, Price, Discount, AbInvoiceID) VALUES (?, ?, ?, ?, ?)");

        foreach ($this->connection->query("
          SELECT
              i.*,
              bi.ServiceName
          FROM
              AbInvoice i
              JOIN AbMessage m ON m.AbMessageID = i.MessageID
              JOIN AbRequest r ON r.AbRequestID = m.RequestID
              JOIN Usr u ON u.UserID = r.BookerUserID
              JOIN AbBookerInfo bi ON bi.UserID = u.UserID
        ") as $row) {
            $stm->execute(['Award Booking Service Fee', $row['Tickets'], $row['Price'], $row['Discount'], $row['AbInvoiceID']]);

            if (!empty($row['Taxes'])) {
                $stm->execute(['Airline Taxes', $row['Tickets'], $row['Taxes'], null, $row['AbInvoiceID']]);
            }
        }
        $this->connection->exec("ALTER TABLE `AbInvoice` DROP `Tickets`;");
        $this->connection->exec("ALTER TABLE `AbInvoice` DROP `Price`;");
        $this->connection->exec("ALTER TABLE `AbInvoice` DROP `Discount`;");
        $this->connection->exec("ALTER TABLE `AbInvoice` DROP `Taxes`;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `AbInvoiceItem`;");
    }
}

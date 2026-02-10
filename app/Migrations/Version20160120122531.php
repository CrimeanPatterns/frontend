<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160120122531 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table EmailNDR drop Cnt");
        $this->addSql("alter table EmailNDRContent add foreign key(EmailNDRID) references EmailNDR(EmailNDRID) on delete cascade");
        $this->addSql("alter table EmailNDR add unique key akEmail(Address)");
        $dups = $this->connection->executeQuery("
        select
            EmailNDRID, MessageID, max(EmailNDRContentID) as LastID, count(*) as Cnt
        from
            EmailNDRContent
        group by
            EmailNDRID, MessageID
        having
            count(*) > 1")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($dups as $row) {
            $affected = $this->connection->executeUpdate(
                "delete from EmailNDRContent where EmailNDRID = :EmailNDRID and MessageID = :MessageID and EmailNDRContentID = :LastID",
                $row
            );
            $this->write("deleted $affected duplicates: " . var_export($row, true));
        }
        $this->addSql("alter table EmailNDRContent add unique key akMessageID(EmailNDRID, MessageID)");
        $this->addSql("alter table EmailNDRContent add MessageDate timestamp");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table EmailNDR add Cnt int not null default 0");
        $this->addSql("alter table EmailNDRContent drop foreign key EmailNDRContent_ibfk_1");
        $this->addSql("alter table EmailNDR drop key akEmail");
        $this->addSql("alter table EmailNDRContent drop key akMessageID");
    }
}

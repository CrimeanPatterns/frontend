<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200805113259 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("INSERT INTO adminLeftNav (parentID, caption, path, rank, note, visible) VALUES (1, 'Debug Parser', '/admin/debugParser.php', 70, null, 1);");
        $this->addSql("INSERT INTO adminLeftNav (parentID, caption, path, rank, note, visible) VALUES (1, 'Search for logs of account', '/admin/accountLogs.php', 72, null, 1);");
        $this->addSql("UPDATE adminLeftNav SET rank = 70 WHERE caption = 'Debug Proxy'");
        $this->addSql("UPDATE adminLeftNav SET rank = 71 WHERE caption = 'Confirmation Debug Proxy'");
        $this->addSql("UPDATE adminLeftNav SET rank = 60 WHERE caption = 'Computer Equipment'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("DELETE FROM adminLeftNav WHERE caption = 'Debug Parser'");
        $this->addSql("DELETE FROM adminLeftNav WHERE caption = 'Search for logs of account'");
        $this->addSql("UPDATE adminLeftNav SET rank = 50 WHERE caption = 'Debug Proxy'");
        $this->addSql("UPDATE adminLeftNav SET rank = 50 WHERE caption = 'Confirmation Debug Proxy'");
        $this->addSql("UPDATE adminLeftNav SET rank = 145 WHERE caption = 'Computer Equipment'");
    }
}

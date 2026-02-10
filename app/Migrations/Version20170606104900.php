<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170606104900 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table BusinessInfo add PublicKey varchar(8000) comment 'Ключ для шифрования паролей в Account Access API'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table BusinessInfo drop PublicKey");
    }
}

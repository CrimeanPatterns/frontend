<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131023161837 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `AbSegment` MODIFY COLUMN `Dep` varchar(250) CHARACTER SET utf8 COLLATE utf8_general_ci;');
        $this->addSql('ALTER TABLE `AbSegment` MODIFY COLUMN `Arr` varchar(250) CHARACTER SET utf8 COLLATE utf8_general_ci;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `AbSegment` MODIFY COLUMN `Dep` varchar(250) CHARACTER SET utf8 COLLATE utf8_unicode_ci;');
        $this->addSql('ALTER TABLE `AbSegment` MODIFY COLUMN `Arr` varchar(250) CHARACTER SET utf8 COLLATE utf8_unicode_ci;');
    }
}

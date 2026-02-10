<?php declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210604134015 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("
            ALTER TABLE `LoungePage`
              MODIFY LoungePageID INT(11) NOT NULL AUTO_INCREMENT
        ");
    }

    public function down(Schema $schema) : void
    {
    }
}

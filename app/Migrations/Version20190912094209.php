<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190912094209 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Region 
            modify Kind int not null COMMENT 'Тип (константа из web/kernel/constants.php, например REGION_KIND_CONTINENT)'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}

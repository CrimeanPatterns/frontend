<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191105100250 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            alter table `EmailTemplate`
                add column `Preview` TEXT default null comment 'превью для почтовых клиентов' after `Logo`
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            alter table `EmailTemplate` drop column `Preview`
        ');
    }
}

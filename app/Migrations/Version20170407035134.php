<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170407035134 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("update Usr set WpNewBlogPosts = 0 where CreationDateTime < '2017-04-04'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}

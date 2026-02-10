<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170621084008 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table AbBookerInfo add PayPalClientId varchar(80) comment 'PayPal REST API', add PayPalSecret varchar(80) comment 'PayPal REST API'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table AbBookerInfo drop PayPalClientId, drop PayPalSecret");
    }
}

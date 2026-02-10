<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140117075258 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable("AbInvoiceMiles")->addColumn("Owner", "string");
    }

    public function down(Schema $schema): void
    {
        $schema->getTable("AbInvoiceMiles")->dropColumn("Owner");
    }
}

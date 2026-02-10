<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140117202958 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('AbAccountProgram')
            ->addColumn("Shared", "boolean", ['default' => null, 'notnull' => false])
            ->setComment('Расшарен ли аккаунт букеру');
    }

    public function down(Schema $schema): void
    {
        $schema->getTable("AbAccountProgram")->dropColumn("Shared");
    }
}

<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190930074017 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        foreach (glob(__DIR__ . '/../../bundles/AwardWallet/MainBundle/FrameworkExtension/Mailer/Template/Offer/Chase/*.php') as $file) {
            $template = basename($file, '.php');

            if (stripos($template, 'Abstract') !== false) {
                continue;
            }
            $this->write("adding template $template");
            $this->connection->executeUpdate("insert into CreditCardEmail(Template, Enabled) values (?, ?)", [$template, 1]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->connection->executeUpdate("delete from CreditCardEmail");
    }
}

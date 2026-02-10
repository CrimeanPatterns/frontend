<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220126061858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("update CreditCardShoppingCategoryGroup set Description = '' where Description is null");
        $this->addSql("alter table CreditCardShoppingCategoryGroup
            modify `Description` mediumtext not null COMMENT 'обьяснения как получить такой multiplier по такой группе категории на такой карте'"
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}

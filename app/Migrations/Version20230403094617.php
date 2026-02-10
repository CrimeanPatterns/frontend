<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230403094617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */"
            ALTER TABLE `Trip`
                ADD INDEX `idxParsed` (`Parsed`),
                ADD INDEX `idxHidden` (`Hidden`),
                ADD INDEX `idxUpdateDate` (`UpdateDate`);
        ");
        $this->addSql(/** @lang MySQL */"
            ALTER TABLE `Reservation`
                ADD INDEX `idxParsed` (`Parsed`),
                ADD INDEX `idxHidden` (`Hidden`),
                ADD INDEX `idxUpdateDate` (`UpdateDate`);
        ");
        $this->addSql(/** @lang MySQL */"
            ALTER TABLE `Rental`
                ADD INDEX `idxParsed` (`Parsed`),
                ADD INDEX `idxHidden` (`Hidden`),
                ADD INDEX `idxUpdateDate` (`UpdateDate`);
        ");
        $this->addSql(/** @lang MySQL */"
            ALTER TABLE `Restaurant`
                ADD INDEX `idxParsed` (`Parsed`),
                ADD INDEX `idxHidden` (`Hidden`),
                ADD INDEX `idxUpdateDate` (`UpdateDate`);
        ");
        $this->addSql(/** @lang MySQL */"
            ALTER TABLE `Parking`
                ADD INDEX `idxParsed` (`Parsed`),
                ADD INDEX `idxHidden` (`Hidden`),
                ADD INDEX `idxUpdateDate` (`UpdateDate`);
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */"
            ALTER TABLE `Trip`
                DROP INDEX `idxParsed`,
                DROP INDEX `idxHidden`,
                DROP INDEX `idxUpdateDate`;
        ");
        $this->addSql(/** @lang MySQL */"
            ALTER TABLE `Reservation`
                DROP INDEX `idxParsed`,
                DROP INDEX `idxHidden`,
                DROP INDEX `idxUpdateDate`;
        ");
        $this->addSql(/** @lang MySQL */"
            ALTER TABLE `Rental`
                DROP INDEX `idxParsed`,
                DROP INDEX `idxHidden`,
                DROP INDEX `idxUpdateDate`;
        ");
        $this->addSql(/** @lang MySQL */"
            ALTER TABLE `Restaurant`
                DROP INDEX `idxParsed`,
                DROP INDEX `idxHidden`,
                DROP INDEX `idxUpdateDate`;
        ");
        $this->addSql(/** @lang MySQL */"
            ALTER TABLE `Parking`
                DROP INDEX `idxParsed`,
                DROP INDEX `idxHidden`,
                DROP INDEX `idxUpdateDate`;
        ");
    }
}

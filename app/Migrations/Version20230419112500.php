<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230419112500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $userIp = $schema->getTable('UserIP');

        if ($userIp->hasIndex('UserIP_IsPointSet')) {
            $this->addSql('alter table UserIP drop index UserIP_IsPointSet');
        }

        $columnsToDrop = [
            'IsPointSet',
            'Point',
            'Lat',
            'Lng',
        ];
        $this->addSql('alter table UserIP ' . $this->getDropSQL($columnsToDrop, $userIp));

        $usr = $schema->getTable('Usr');

        if ($usr->hasIndex('Usr_FirstName_MidName_LastName_findex')) {
            $this->addSql('alter table Usr drop index Usr_FirstName_MidName_LastName_findex');
            $this->addSql('optimize table Usr');
        }

        if ($usr->hasIndex('Usr_IsLastLogonPointSet')) {
            $this->addSql('alter table Usr drop index Usr_IsLastLogonPointSet');
        }

        if ($usr->hasIndex('Usr_IsResidentPointSet')) {
            $this->addSql('alter table Usr drop index Usr_IsResidentPointSet');
        }

        $columnsToDrop = [
            'ResidentPoint',
            'ResidentLat',
            'ResidentLng',
            'LastLogonPoint',
            'LastLogonLat',
            'LastLogonLng',
            'IsResidentPointSet',
            'IsLastLogonPointSet',
        ];

        $this->addSql('alter table Usr ' . $this->getDropSQL($columnsToDrop, $usr));
    }

    protected function getDropSQL(array $columnsToDrop, Table $table): string
    {
        return
            it($columnsToDrop)
            ->filter(fn ($column) => $table->hasColumn($column))
            ->map(fn ($column) => "drop column {$column}, algorithm = instant")
            ->joinToString(', ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}

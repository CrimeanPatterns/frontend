<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210607141752 extends AbstractMigration
{

    const STEP = 10000;

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $maxAccountId = $this->connection->fetchOne("select max(AccountID) from Account");
        $this->write("max accountId: $maxAccountId");

        $startAccountId = 1;
        $q = $this->connection->prepare("update Account a, Provider p set a.HistoryVersion = p.CacheVersion 
        where a.ProviderID = p.ProviderID and a.AccountID >= :startAccountId and a.AccountID < :endAccountId");

        while ($startAccountId < $maxAccountId) {
            $this->write("accountId: $startAccountId");
            $endAccountId = $startAccountId + self::STEP;
            $q->executeStatement(["startAccountId" => $startAccountId, "endAccountId" => $endAccountId]);
            $startAccountId = $endAccountId;
            $this->connection->commit();
            $this->connection->beginTransaction();
        }

        $this->write("done");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }

}

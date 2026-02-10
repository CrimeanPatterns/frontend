<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150618055727 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        foreach ($this->connection->executeQuery("select OfferID from Offer where Enabled = 1")->fetchAll(\PDO::FETCH_COLUMN) as $offerId) {
            $this->write("updating offer $offerId");
            $maxUserId = $this->connection->executeQuery("select max(UserID) from OfferUser where OfferID = $offerId")->fetchColumn(0);
            $this->write("max user id: " . $maxUserId);
            $this->connection->executeUpdate("update Offer set LastUserID = ? where OfferID = ?", [$maxUserId, $offerId]);
        }
    }

    public function down(Schema $schema): void
    {
    }
}

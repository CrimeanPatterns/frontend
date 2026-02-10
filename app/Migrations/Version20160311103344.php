<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160311103344 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->connection->executeUpdate("insert into SiteGroup(GroupName, Description) values(?, ?)", [
            'Big 3 updating', 'allow big 3 updating',
        ]);
        $groupId = $this->connection->lastInsertId();
        $this->addUsersToGroup(['siteadmin'], $groupId);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }

    protected function addUsersToGroup(array $users, $groupId)
    {
        foreach ($users as $login) {
            $userId = $this->connection->executeQuery("select UserID from Usr where Login = ?", [$login])->fetchColumn(0);

            if ($userId === false) {
                continue;
            }
            $this->addSql("insert into GroupUserLink(SiteGroupID, UserID) values(?, ?)", [$groupId, $userId]);
        }
    }
}

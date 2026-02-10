<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * remove duplicate entries from AccountProperty.
 */
class Version20130730034845 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $progress = 0;
        $this->write("loading duplicate entries");

        foreach ($this->connection->executeQuery("select
			AccountID, SubAccountID, ProviderPropertyID,
			count(AccountPropertyID) as Duplicates,
			max(AccountPropertyID) as MaxID
		from AccountProperty
		group by AccountID, SubAccountID, ProviderPropertyID
		having count(AccountPropertyID) > 1") as $row) {
            if (is_null($row['SubAccountID'])) {
                $sql = "delete from AccountProperty
				where AccountID = :AccountID and SubAccountID is null
				and ProviderPropertyID = :ProviderPropertyID and AccountPropertyID <> :MaxID";
            } else {
                $sql = "delete from AccountProperty
				where AccountID = :AccountID and SubAccountID = :SubAccountID
				and ProviderPropertyID = :ProviderPropertyID and AccountPropertyID <> :MaxID";
            }
            $affected = $this->connection->executeUpdate($sql, $row);

            if ($affected == 0) {
                throw new \Exception("can't delete rows");
            }
            $progress = $progress + $row['Duplicates'] - 1;

            if (($progress % 100) == 0) {
                $this->write("deleted $progress records");
            }
        }
        $this->write("total deleted: $progress");
    }

    public function down(Schema $schema): void
    {
        $this->write("can't undo this migration");
    }
}

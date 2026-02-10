<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Globals\StringUtils;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170707090751 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $providersStmt = $this->connection->executeQuery("
            select
                p.ProviderID,
                p.LoginURL,
                GROUP_CONCAT(pc.LoginURL separator '') as `LoginURLs`
            from Provider p
            join ProviderCountry pc on p.ProviderID = pc.ProviderID
            where 
                p.IsRetail = 1 and 
                p.AutoLogin = 0 
            group by p.ProviderID
        ");

        while ($provider = $providersStmt->fetch(\PDO::FETCH_ASSOC)) {
            if (
                !StringUtils::isEmpty($provider['LoginURL'])
                || !StringUtils::isEmpty($provider['LoginURLs'])
            ) {
                $this->connection->executeUpdate(
                    'update Provider set AutoLogin = ? where ProviderID = ? and AutoLogin = 0',
                    [AUTOLOGIN_SERVER, $provider['ProviderID']]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}

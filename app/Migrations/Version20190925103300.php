<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class Version20190925103300 extends AbstractMigration implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        /** @var EntityManager $connection */
        $connection = $this->container->get('doctrine.orm.entity_manager')->getConnection();
        $accountsWithExpiration = $connection->fetchAll('
            SELECT
                    AccountID, ExpirationDate
            FROM
                    Account
            WHERE
                    ProviderID = 26
                AND ExpirationDate IS NOT NULL
        ');

        if (!empty($accountsWithExpiration)) {
            $accountIds = array_column($accountsWithExpiration, 'AccountID');
            $accountIdsChunk = array_chunk($accountIds, 500);
            $affected = 0;

            foreach ($accountIdsChunk as $accIds) {
                $affected += $connection->executeUpdate('UPDATE Account SET ExpirationDate = NULL WHERE AccountID IN(' . implode(',', $accIds) . ')');
            }

            echo 'Dropped ExpirationDate for ' . $affected . ' accounts';

            //print_r($accountsWithExpiration);

            return;
        }

        echo 'Accounts for reset not found';
    }

    public function down(Schema $schema): void
    {
    }
}

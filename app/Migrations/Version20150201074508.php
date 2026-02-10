<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Globals\StringHandler;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version20150201074508 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        $params = $this->connection->getParams();
        $params['driverOptions'][\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = false;
        $cn1 = new Connection($params, $this->connection->getDriver());
        $cn2 = new Connection($params, $this->connection->getDriver());

        foreach (['Trip', 'Reservation', 'Rental', 'Restaurant'] as $table) {
            $q = $cn1->executeQuery("SELECT {$table}ID AS ID from {$table} WHERE ShareCode IS NULL OR ShareCode = ''");
            $u = $cn2->prepare("UPDATE {$table} SET ShareCode = ? WHERE {$table}ID = ?");
            $rows = 0;

            while ($id = $q->fetchColumn(0)) {
                $u->execute([StringHandler::getRandomCode(20), $id]);
                $rows++;

                if (($rows % 10000) == 0) {
                    $this->write("updated $rows rows in $table table");
                }
            }
            $this->write("updated $rows rows in $table table");
        }
    }

    public function down(Schema $schema): void
    {
    }
}

<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version20160712221544 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->em = $container->get('doctrine.orm.default_entity_manager');
    }

    public function up(Schema $schema): void
    {
        $sql = "select i.BusinessInfoID, i.UserID from BusinessInfo i where i.APIKey = ''";

        foreach ($this->connection->query($sql) as $row) {
            $key = sha1($row["UserID"] . microtime());
            $this->addSql('update BusinessInfo set APIKey = ? where BusinessInfoID = ?',
                [$key, $row["BusinessInfoID"]],
                [\PDO::PARAM_STR, \PDO::PARAM_INT]);
        }
    }

    public function down(Schema $schema): void
    {
    }
}

<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140123024355 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var UseragentRepository
     */
    protected $repo;
    /**
     * @var EntityManager
     */
    protected $em;
    /**
     * @var Connection
     */
    protected $connection;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->repo = $container->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $this->em = $container->get('doctrine.orm.entity_manager');
        $this->connection = $container->get('doctrine.dbal.default_connection');
    }

    public function up(Schema $schema): void
    {
        foreach ($this->repo->findBy(['clientid' => null, 'alias' => null]) as $agent) {
            /** @var Useragent $agent */
            $alias = $this->repo->createAlias($agent->getAgentid(), $agent->getFirstname(), $agent->getLastname());
            $this->write("set alias {$alias} for {$agent->getUseragentid()}");
            $this->connection->executeQuery("update UserAgent set Alias = ? where UserAgentID = ?", [$alias, $agent->getUseragentid()], [\PDO::PARAM_STR, \PDO::PARAM_INT]);
            //			$agent->setAlias($alias);
            //			$this->em->persist($agent);
            //			$this->em->flush();
        }
    }

    public function down(Schema $schema): void
    {
    }
}
